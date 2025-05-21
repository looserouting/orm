#!/usr/bin/env php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Orm\Migration\EntitySchemaExtractor;
use Orm\Migration\InitialSchemaSqlGenerator;
use Orm\Migration\SchemaComparator;
use Orm\Migration\MigrationSqlGenerator;

// Konfiguration (ggf. anpassen)
$entityNamespace = 'Orm\Entity';
$entityPath = __DIR__ . '/../src/Entity';
$migrationsPath = __DIR__ . '/../migrations';
$schemaStateFile = $migrationsPath . '/_last_schema.json';
$dbDsn = 'sqlite:' . __DIR__ . '/../db.sqlite'; // Beispiel für SQLite
$dbUser = null;
$dbPass = null;
$migrationTable = '_migrations';

if (!is_dir($migrationsPath)) {
    mkdir($migrationsPath, 0777, true);
}

$usage = <<<USAGE
Usage:
  php migrate.php init                # Erstellt initiales Schema-SQL und Snapshot
  php migrate.php create              # Erzeugt Up/Down-Migration durch Schema-Vergleich
  php migrate.php status              # Zeigt angewendete und verfügbare Migrationen
  php migrate.php up [--to=VERSION]   # Führt Up-Migrationen bis zu einer Version aus
  php migrate.php down [--to=VERSION] # Führt Down-Migrationen bis zu einer Version aus
USAGE;

$cmd = $argv[1] ?? null;
if (!$cmd || !in_array($cmd, ['init', 'create', 'status', 'up', 'down'])) {
    echo $usage . PHP_EOL;
    exit(1);
}

// Hilfsfunktionen für Runner
function getMigrationFiles($migrationsPath, $direction = 'up') {
    $files = glob($migrationsPath . '/*_migration_' . $direction . '.sql');
    usort($files, function($a, $b) {
        return strcmp(basename($a), basename($b));
    });
    return $files;
}

function getVersionFromFilename($filename) {
    if (preg_match('/(\d{8}_\d{6})_migration_(up|down)\.sql$/', $filename, $m)) {
        return $m[1];
    }
    return null;
}

function ensureMigrationTable($pdo, $migrationTable) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `$migrationTable` (
        version VARCHAR(32) PRIMARY KEY,
        applied_at DATETIME NOT NULL
    )");
}

function getAppliedVersions($pdo, $migrationTable) {
    $stmt = $pdo->query("SELECT version FROM `$migrationTable` ORDER BY version ASC");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
}

function applySqlFile($pdo, $file) {
    $sql = file_get_contents($file);
    $pdo->exec($sql);
}

// DB-Verbindung für Runner-Kommandos
if (in_array($cmd, ['status', 'up', 'down'])) {
    $pdo = new PDO($dbDsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    ensureMigrationTable($pdo, $migrationTable);
}

switch ($cmd) {
    case 'init':
        echo "Extrahiere Entity-Schema...\n";
        $extractor = new EntitySchemaExtractor($entityNamespace, $entityPath);
        $schema = $extractor->extractSchema();

        echo "Generiere initiales CREATE TABLE SQL...\n";
        $generator = new InitialSchemaSqlGenerator();
        $sql = $generator->generate($schema);

        $file = $migrationsPath . '/0001_initial_schema.sql';
        file_put_contents($file, $sql);
        file_put_contents($schemaStateFile, json_encode($schema, JSON_PRETTY_PRINT));
        echo "Initiales Schema geschrieben nach: $file\n";
        echo "Schema-Snapshot gespeichert: $schemaStateFile\n";
        break;

    case 'create':
        if (!file_exists($schemaStateFile)) {
            echo "Kein vorheriges Schema gefunden. Bitte zuerst 'init' ausführen.\n";
            exit(1);
        }
        echo "Lade vorheriges Schema...\n";
        $oldSchema = json_decode(file_get_contents($schemaStateFile), true);

        echo "Extrahiere aktuelles Entity-Schema...\n";
        $extractor = new EntitySchemaExtractor($entityNamespace, $entityPath);
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
        $upFile = $migrationsPath . "/{$timestamp}_migration_up.sql";
        $downFile = $migrationsPath . "/{$timestamp}_migration_down.sql";

        file_put_contents($upFile, $upSql);
        file_put_contents($downFile, $downSql);
        file_put_contents($schemaStateFile, json_encode($newSchema, JSON_PRETTY_PRINT));

        echo "Up-Migration geschrieben nach: $upFile\n";
        echo "Down-Migration geschrieben nach: $downFile\n";
        echo "Schema-Snapshot aktualisiert: $schemaStateFile\n";
        break;
        
    case 'status':
        $applied = getAppliedVersions($pdo, $migrationTable);
        $allUp = array_map('basename', getMigrationFiles($migrationsPath, 'up'));
        echo "Angewendete Migrationen:\n";
        foreach ($applied as $v) {
            echo "  $v\n";
        }
        echo "\nVerfügbare Migrationen:\n";
        foreach ($allUp as $file) {
            $ver = getVersionFromFilename($file);
            echo "  $ver\n";
        }
        break;

    case 'up':
        $toVersion = null;
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--to=')) {
                $toVersion = substr($arg, 5);
            }
        }
        $applied = getAppliedVersions($pdo, $migrationTable);
        $files = getMigrationFiles($migrationsPath, 'up');
        foreach ($files as $file) {
            $version = getVersionFromFilename($file);
            if (in_array($version, $applied)) continue;
            if ($toVersion && strcmp($version, $toVersion) > 0) break;
            echo "Applying $version (up)...\n";
            applySqlFile($pdo, $file);
            $stmt = $pdo->prepare("INSERT INTO `$migrationTable` (version, applied_at) VALUES (?, ?)");
            $stmt->execute([$version, date('Y-m-d H:i:s')]);
        }
        echo "Migration up abgeschlossen.\n";
        break;

    case 'down':
        $toVersion = null;
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--to=')) {
                $toVersion = substr($arg, 5);
            }
        }
        $applied = getAppliedVersions($pdo, $migrationTable);
        $files = array_reverse(getMigrationFiles($migrationsPath, 'down'));
        foreach ($files as $file) {
            $version = getVersionFromFilename($file);
            if (!in_array($version, $applied)) continue;
            if ($toVersion && strcmp($version, $toVersion) <= 0) break;
            echo "Reverting $version (down)...\n";
            applySqlFile($pdo, $file);
            $stmt = $pdo->prepare("DELETE FROM `$migrationTable` WHERE version = ?");
            $stmt->execute([$version]);
        }
        echo "Migration down abgeschlossen.\n";
        break;
}
