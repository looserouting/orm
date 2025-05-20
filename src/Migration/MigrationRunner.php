<?php
namespace Orm\Migration;

use PDO;

class MigrationRunner
{
    public function __construct(private PDO $pdo) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS _migrations (version TEXT PRIMARY KEY)");
    }

    public function run(array $migrations): void {
        foreach ($migrations as $version => $migration) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM _migrations WHERE version = ?");
            $stmt->execute([$version]);
            if ($stmt->fetchColumn() == 0) {
                $migration->up($this->pdo);
                $this->pdo->prepare("INSERT INTO _migrations (version) VALUES (?)")->execute([$version]);
            }
        }
    }
}
