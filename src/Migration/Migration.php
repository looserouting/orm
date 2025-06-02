<?php

namespace Orm\Migration;

use PDO;

class Migration
{
    private string $entityNamespace;
    private string $entityPath;
    private string $migrationsPath;
    private string $schemaStateFile;
    private string $dbDsn;
    private ?string $dbUser;
    private ?string $dbPass;
    private string $migrationTable;

    public function __construct(
        string $entityNamespace,
        string $entityPath,
        string $migrationsPath,
        string $schemaStateFile,
        string $dbDsn,
        ?string $dbUser,
        ?string $dbPass,
        string $migrationTable = '_migrations'
    ) {
        $this->entityNamespace = $entityNamespace;
        $this->entityPath = $entityPath;
        $this->migrationsPath = $migrationsPath;
        $this->schemaStateFile = $schemaStateFile;
        $this->dbDsn = $dbDsn;
        $this->dbUser = $dbUser;
        $this->dbPass = $dbPass;
        $this->migrationTable = $migrationTable;
    }

    public function init(): void
    {
        echo "Extrahiere Entity-Schema...\n";
        $extractor = new EntitySchemaExtractor($this->entityNamespace, $this->entityPath);
        $schema = $extractor->extractSchema();

        echo "Generiere initiales CREATE TABLE SQL...\n";
        $generator = new InitialSchemaSqlGenerator();
        $sql = $generator->generate($schema);

        $file = $this->migrationsPath . '/0001_initial_schema.sql';
        file_put_contents($file, $sql);
        file_put_contents($this->schemaStateFile, json_encode($schema, JSON_PRETTY_PRINT));
        echo "Initiales Schema geschrieben nach: $file\n";
        echo "Schema-Snapshot gespeichert: {$this->schemaStateFile}\n";
    }

    public function create(): void
    {
        if (!file_exists($this->schemaStateFile)) {
            echo "Kein vorheriges Schema gefunden. Bitte zuerst 'init' ausführen.\n";
            exit(1);
        }
        echo "Lade vorheriges Schema...\n";
        $oldSchema = json_decode(file_get_contents($this->schemaStateFile), true);

        echo "Extrahiere aktuelles Entity-Schema...\n";
        $extractor = new EntitySchemaExtractor($this->entityNamespace, $this->entityPath);
        $newSchema = $extractor->extractSchema();

        $comparator = new SchemaComparator();
        $diffs = $comparator->compare($oldSchema, $newSchema);

        if (empty($diffs['up'])) {
            echo "Keine Änderungen erkannt. Keine Migration erzeugt.\n";
            exit(0);
        }

        $generator = new MigrationSqlGenerator();
        $upSql = $generator->generate($diffs['up']);
        $downSql = $generator->generate($diffs['down']);

        $timestamp = date('Ymd_His');
        $upFile = $this->migrationsPath . "/{$timestamp}_migration_up.sql";
        $downFile = $this->migrationsPath . "/{$timestamp}_migration_down.sql";

        file_put_contents($upFile, $upSql);
        file_put_contents($downFile, $downSql);
        file_put_contents($this->schemaStateFile, json_encode($newSchema, JSON_PRETTY_PRINT));

        echo "Up-Migration geschrieben nach: $upFile\n";
        echo "Down-Migration geschrieben nach: $downFile\n";
        echo "Schema-Snapshot aktualisiert: {$this->schemaStateFile}\n";
    }

    public function status(): void
    {
        $pdo = $this->getPdo();
        $this->ensureMigrationTable($pdo);

        $applied = $this->getAppliedVersions($pdo);
        $allUp = array_map('basename', $this->getMigrationFiles('up'));
        echo "Angewendete Migrationen:\n";
        foreach ($applied as $v) {
            echo "  $v\n";
        }
        echo "\nVerfügbare Migrationen:\n";
        foreach ($allUp as $file) {
            $ver = $this->getVersionFromFilename($file);
            echo "  $ver\n";
        }
    }

    public function up(?string $toVersion = null): void
    {
        $pdo = $this->getPdo();
        $this->ensureMigrationTable($pdo);

        $applied = $this->getAppliedVersions($pdo);
        $files = $this->getMigrationFiles('up');
        foreach ($files as $file) {
            $version = $this->getVersionFromFilename($file);
            if (in_array($version, $applied)) continue;
            if ($toVersion && strcmp($version, $toVersion) > 0) break;
            echo "Applying $version (up)...\n";
            $this->applySqlFile($pdo, $file);
            $stmt = $pdo->prepare("INSERT INTO `{$this->migrationTable}` (version, applied_at) VALUES (?, ?)");
            $stmt->execute([$version, date('Y-m-d H:i:s')]);
        }
        echo "Migration up abgeschlossen.\n";
    }

    public function down(?string $toVersion = null): void
    {
        $pdo = $this->getPdo();
        $this->ensureMigrationTable($pdo);

        $applied = $this->getAppliedVersions($pdo);
        $files = array_reverse($this->getMigrationFiles('down'));
        foreach ($files as $file) {
            $version = $this->getVersionFromFilename($file);
            if (!in_array($version, $applied)) continue;
            if ($toVersion && strcmp($version, $toVersion) <= 0) break;
            echo "Reverting $version (down)...\n";
            $this->applySqlFile($pdo, $file);
            $stmt = $pdo->prepare("DELETE FROM `{$this->migrationTable}` WHERE version = ?");
            $stmt->execute([$version]);
        }
        echo "Migration down abgeschlossen.\n";
    }

    // --- Hilfsmethoden ---

    private function getPdo(): PDO
    {
        $pdo = new PDO($this->dbDsn, $this->dbUser, $this->dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    private function ensureMigrationTable(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$this->migrationTable}` (
            version VARCHAR(32) PRIMARY KEY,
            applied_at DATETIME NOT NULL
        )");
    }

    private function getAppliedVersions(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT version FROM `{$this->migrationTable}` ORDER BY version ASC");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }

    private function getMigrationFiles(string $direction = 'up'): array
    {
        $files = glob($this->migrationsPath . '/*_migration_' . $direction . '.sql');
        usort($files, function($a, $b) {
            return strcmp(basename($a), basename($b));
        });
        return $files;
    }

    private function getVersionFromFilename(string $filename): ?string
    {
        if (preg_match('/(\d{8}_\d{6})_migration_(up|down)\.sql$/', $filename, $m)) {
            return $m[1];
        }
        return null;
    }

    private function applySqlFile(PDO $pdo, string $file): void
    {
        $sql = file_get_contents($file);
        $pdo->exec($sql);
    }
}