#!/usr/bin/env php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Orm\Migration\Migration;

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

$migration = new Migration(
    $entityNamespace,
    $entityPath,
    $migrationsPath,
    $schemaStateFile,
    $dbDsn,
    $dbUser,
    $dbPass,
    $migrationTable
);

switch ($cmd) {
    case 'init':
        $migration->init();
        break;
    case 'create':
        $migration->create();
        break;
    case 'status':
        $migration->status();
        break;
    case 'up':
        $toVersion = null;
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--to=')) {
                $toVersion = substr($arg, 5);
            }
        }
        $migration->up($toVersion);
        break;
    case 'down':
        $toVersion = null;
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--to=')) {
                $toVersion = substr($arg, 5);
            }
        }
        $migration->down($toVersion);
        break;
}