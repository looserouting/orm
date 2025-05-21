<?php
namespace Orm\Migration;

class MigrationSqlGenerator
{
    /**
     * @param array $diffs
     * @return string SQL
     */
    public function generate(array $diffs): string
    {
        $sql = '';
        foreach ($diffs as $diff) {
            switch ($diff['action']) {
                case 'create_table':
                    $sql .= $this->generateCreateTable($diff['table'], $diff['columns']) . ";\n";
                    break;
                case 'drop_table':
                    $sql .= "DROP TABLE IF EXISTS `{$diff['table']}`;\n";
                    break;
                case 'add_column':
                    $sql .= "ALTER TABLE `{$diff['table']}` ADD COLUMN " . $this->columnDef($diff['column'], $diff['definition']) . ";\n";
                    break;
                case 'drop_column':
                    $sql .= "ALTER TABLE `{$diff['table']}` DROP COLUMN `{$diff['column']}`;\n";
                    break;
                case 'modify_column':
                    $sql .= "ALTER TABLE `{$diff['table']}` MODIFY COLUMN " . $this->columnDef($diff['column'], $diff['definition']) . ";\n";
                    break;
            }
        }
        return $sql;
    }

    private function generateCreateTable(string $table, array $columns): string
    {
        $lines = [];
        $primaryKeys = [];
        $uniqueKeys = [];

        foreach ($columns as $name => $col) {
            $type = $this->phpTypeToSql($col['type']);
            $nullable = $col['nullable'] ? 'NULL' : 'NOT NULL';
            $line = "`$name` $type $nullable";

            if (!empty($col['auto_increment']) && $col['auto_increment'] === true) {
                $line .= " AUTO_INCREMENT";
            }
            if (array_key_exists('default', $col)) {
                $default = $col['default'];
                if ($default === null) {
                    $line .= " DEFAULT NULL";
                } elseif (is_string($default)) {
                    $line .= " DEFAULT '" . addslashes($default) . "'";
                } elseif (is_bool($default)) {
                    $line .= " DEFAULT " . ($default ? '1' : '0');
                } else {
                    $line .= " DEFAULT $default";
                }
            }
            if (!empty($col['unique']) && $col['unique'] === true) {
                $uniqueKeys[] = $name;
            }
            if (!empty($col['primary']) && $col['primary'] === true) {
                $primaryKeys[] = $name;
            }
            $lines[] = $line;
        }
        if (!empty($primaryKeys)) {
            $lines[] = "PRIMARY KEY (`" . implode('`,`', $primaryKeys) . "`)";
        }
        foreach ($uniqueKeys as $uniqueCol) {
            $lines[] = "UNIQUE (`$uniqueCol`)";
        }
        $columnsSql = implode(",\n  ", $lines);
        return "CREATE TABLE `$table` (\n  $columnsSql\n)";
    }

    private function columnDef(string $name, array $col): string
    {
        $type = $this->phpTypeToSql($col['type']);
        $nullable = $col['nullable'] ? 'NULL' : 'NOT NULL';
        $line = "`$name` $type $nullable";
        if (!empty($col['auto_increment']) && $col['auto_increment'] === true) {
            $line .= " AUTO_INCREMENT";
        }
        if (array_key_exists('default', $col)) {
            $default = $col['default'];
            if ($default === null) {
                $line .= " DEFAULT NULL";
            } elseif (is_string($default)) {
                $line .= " DEFAULT '" . addslashes($default) . "'";
            } elseif (is_bool($default)) {
                $line .= " DEFAULT " . ($default ? '1' : '0');
            } else {
                $line .= " DEFAULT $default";
            }
        }
        if (!empty($col['unique']) && $col['unique'] === true) {
            $line .= " UNIQUE";
        }
        return $line;
    }

    private function phpTypeToSql(string $phpType): string
    {
        return match($phpType) {
            'int', 'integer' => 'INTEGER',
            'string' => 'VARCHAR(255)',
            'float' => 'REAL',
            'bool', 'boolean' => 'BOOLEAN',
            default => 'TEXT',
        };
    }
}