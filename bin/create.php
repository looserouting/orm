#!/usr/bin/env php
<?php
/**
 * CLI-Skript zum schnellen Erstellen von Entity und Repository.
 * Usage: php bin/create.php entity Blubb
 * Der Namespace wird aus composer.json (autoload->psr-4) des aufrufenden Projekts gelesen.
 */

function getProjectNamespace(): ?string {
    $composerFile = getcwd() . '/composer.json'; // WICHTIG: composer.json des Projekts!
    if (!file_exists($composerFile)) {
        return null;
    }
    $composer = json_decode(file_get_contents($composerFile), true);
    if (!isset($composer['autoload']['psr-4'])) {
        return null;
    }
    $psr4 = $composer['autoload']['psr-4'];
    // Nimm den ersten Namespace-Eintrag
    foreach ($psr4 as $ns => $path) {
        // Entferne abschließende Backslashes
        return rtrim($ns, '\\');
    }
    return null;
}

function getSrcDir(): string {
    $composerFile = getcwd() . '/composer.json';
    if (!file_exists($composerFile)) {
        return getcwd() . '/src';
    }
    $composer = json_decode(file_get_contents($composerFile), true);
    if (!isset($composer['autoload']['psr-4'])) {
        return getcwd() . '/src';
    }
    $psr4 = $composer['autoload']['psr-4'];
    foreach ($psr4 as $ns => $path) {
        // $path kann z.B. "src/" sein
        return rtrim(getcwd() . '/' . $path, '/');
    }
    return getcwd() . '/src';
}

if ($argc < 3 || $argv[1] !== 'entity') {
    echo "Usage: php bin/create.php entity <Name>\n";
    exit(1);
}

$name = $argv[2];
$entityName = ucfirst($name) . 'Entity';
$repositoryName = ucfirst($name) . 'Repository';

$projectNamespace = getProjectNamespace();
if (!$projectNamespace) {
    echo "Konnte Namespace nicht aus composer.json lesen. Bitte prüfe autoload->psr-4.\n";
    exit(1);
}

$srcDir = getSrcDir();
$entityNamespace = $projectNamespace . "\\Entity";
$repositoryNamespace = $projectNamespace . "\\Repository";

$entityDir = $srcDir . '/Entity';
$repositoryDir = $srcDir . '/Repository';

$entityFile = "$entityDir/{$entityName}.php";
$repositoryFile = "$repositoryDir/{$repositoryName}.php";

// Entity-Template
$entityTemplate = <<<PHP
<?php
namespace $entityNamespace;

use Orm\Entity\BaseEntity;

class $entityName extends BaseEntity
{
    // TODO: Füge hier deine Properties hinzu
    public int \$id = 0;
    public string \$name = '';
}
PHP;

// Repository-Template
$repositoryTemplate = <<<PHP
<?php
namespace $repositoryNamespace;

use Orm\Repository\BaseRepository;

class $repositoryName extends BaseRepository
{
    // TODO: Füge hier deine Repository-Methoden hinzu
}
PHP;

// Verzeichnisse anlegen, falls nicht vorhanden
if (!is_dir($entityDir)) mkdir($entityDir, 0777, true);
if (!is_dir($repositoryDir)) mkdir($repositoryDir, 0777, true);

// Entity-Datei erzeugen
if (file_exists($entityFile)) {
    echo "Entity $entityName existiert bereits!\n";
} else {
    file_put_contents($entityFile, $entityTemplate);
    echo "Entity $entityName wurde erstellt: $entityFile\n";
}

// Repository-Datei erzeugen
if (file_exists($repositoryFile)) {
    echo "Repository $repositoryName existiert bereits!\n";
} else {
    file_put_contents($repositoryFile, $repositoryTemplate);
    echo "Repository $repositoryName wurde erstellt: $repositoryFile\n";
}