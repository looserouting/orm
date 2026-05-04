<?php
namespace Orm\Migration;

use ReflectionClass;
use ReflectionProperty;
use Orm\Attribute\Id;
use Orm\Attribute\Column;

class EntitySchemaExtractor
{
    private string $entityNamespace;
    private string $entityPath;

    public function __construct(string $entityNamespace, string $entityPath)
    {
        $this->entityNamespace = rtrim($entityNamespace, '\\');
        $this->entityPath = rtrim($entityPath, '/');
    }

    public function extractSchema(): array
    {
        $schema = [];
        foreach ($this->findEntityClasses() as $className) {
            $reflection = new ReflectionClass($className);
            if ($reflection->isAbstract()) continue;

            $tableName = $this->classToTableName($reflection->getShortName());
            $columns = [];
            
            foreach ($reflection->getProperties() as $property) {
                // Skip internal ORM properties if any (not expected here but safe)
                if ($property->getDeclaringClass()->getName() === 'Orm\Entity\BaseEntity') {
                    continue;
                }

                $type = $property->getType();
                $name = $property->getName();
                
                $colDef = [
                    'type' => $type ? $type->getName() : 'string',
                    'nullable' => $type ? $type->allowsNull() : true,
                    'primary' => false,
                    'unique' => false,
                    'auto_increment' => false,
                ];

                // Check for Id attribute
                if ($property->getAttributes(Id::class)) {
                    $colDef['primary'] = true;
                    if ($colDef['type'] === 'int') {
                        $colDef['auto_increment'] = true;
                        $colDef['nullable'] = false;
                    }
                }

                // Check for Column attribute
                $colAttr = $property->getAttributes(Column::class);
                if (!empty($colAttr)) {
                    $col = $colAttr[0]->newInstance();
                    if ($col->name) {
                        $name = $col->name;
                    }
                    if ($col->type) {
                        $colDef['type'] = $col->type;
                    }
                    if ($col->unique) {
                        $colDef['unique'] = true;
                    }
                    $colDef['nullable'] = $col->nullable;
                }

                $columns[$name] = $colDef;
            }

            $schema[$tableName] = [
                'columns' => $columns,
            ];
        }
        return $schema;
    }

    private function findEntityClasses(): array
    {
        $classes = [];
        if (!is_dir($this->entityPath)) return [];
        
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->entityPath));
        foreach ($rii as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = $this->fileToClassName($file->getPathname());
                if (class_exists($className)) {
                    $classes[] = $className;
                }
            }
        }
        return $classes;
    }

    private function fileToClassName(string $filePath): string
    {
        $relativePath = str_replace(realpath($this->entityPath) . '/', '', realpath($filePath));
        $class = str_replace('/', '\\', substr($relativePath, 0, -4));
        return $this->entityNamespace . '\\' . $class;
    }

    private function classToTableName(string $classShortName): string
    {
        $name = preg_replace('/Entity$/', '', $classShortName);
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name)) . 's';
    }
}