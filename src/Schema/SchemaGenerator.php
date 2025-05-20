<?php
namespace Orm\Schema;

use Orm\Schema\Attribute\Table;
use Orm\Schema\Attribute\Column;
use ReflectionClass;
use ReflectionProperty;

class SchemaGenerator
{
    public function generateSQL(string $entityClass): string {
        $rc = new ReflectionClass($entityClass);
        $tableAttr = $rc->getAttributes(Table::class)[0] ?? null;
        $tableName = $tableAttr?->newInstance()->name ?? strtolower($rc->getShortName());

        $columns = [];
        foreach ($rc->getProperties() as $property) {
            $colAttr = $property->getAttributes(Column::class)[0] ?? null;
            if ($colAttr) {
                $col = $colAttr->newInstance();
                $line = "`{$col->name}` {$col->type}";
                if ($col->primary) $line .= " PRIMARY KEY";
                if ($col->autoIncrement) $line .= " AUTOINCREMENT";
                if (!$col->nullable) $line .= " NOT NULL";
                $columns[] = $line;
            }
        }

        $columnsSql = implode(",\n  ", $columns);
        return "CREATE TABLE IF NOT EXISTS `$tableName` (\n  $columnsSql\n);";
    }
}
