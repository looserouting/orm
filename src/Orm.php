<?php
namespace Orm;

use PDO;
use Orm\Schema\SchemaGenerator;
use Orm\Migration\MigrationRunner;

class Orm
{
    public function __construct(
        public PDO $pdo,
        public SchemaGenerator $schemaGenerator = new SchemaGenerator(),
        public MigrationRunner $migrationRunner = null
    ) {
        $this->migrationRunner ??= new MigrationRunner($pdo);
    }
}
