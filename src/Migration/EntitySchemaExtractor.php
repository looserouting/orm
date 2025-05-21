<?php
namespace Orm\Migration;

use ReflectionClass;
use ReflectionProperty;

class EntitySchemaExtractor
{
    private string $entityNamespace;
    private string $entityPath;

    /**
     * @param string $entityNamespace z.B. 'Orm\Entity'
     * @param string $entityPath z.B. '/path/to/src/Entity'
     */
    public function __construct(string $entityNamespace, string $entityPath)
    {
        $this->entityNamespace = rtrim($entityNamespace, '\\');
        $this->entityPath = rtrim($entityPath, '/');
    }

    /**
     * Findet alle Entity-Klassen und gibt das Soll-Schema als Array zurück.
     * [
     *   'users' => [
     *     'columns' => [
     *       'id' => ['type' => 'int', 'nullable' => false, ...],
     *       'name' => ['type' => 'string', 'nullable' => false, ...],
     *     ],
     *     ...
     *   ],
     *   ...
     * ]
     */
    public function extractSchema(): array
    {
        $schema = [];
        foreach ($this->findEntityClasses() as $className) {
            $reflection = new ReflectionClass($className);
            $tableName = $this->classToTableName($reflection->getShortName());
            $columns = [];
            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED) as $property) {
                $type = $property->getType();
                $columns[$property->getName()] = [
                    'type' => $type ? $type->getName() : 'mixed',
                    'nullable' => $type ? $type->allowsNull() : true,
                    // Hier könnten weitere Attribute/Annotationen ausgewertet werden (z.B. PrimaryKey, Unique, etc.)
                ];
            }
            $schema[$tableName] = [
                'columns' => $columns,
                // Weitere Metadaten wie PrimaryKey, Indizes, etc. können ergänzt werden
            ];
        }
        return $schema;
    }

    /**
     * Findet alle Entity-Klassen im angegebenen Verzeichnis.
     * Gibt vollqualifizierte Klassennamen zurück.
     */
    private function findEntityClasses(): array
    {
        $classes = [];
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

    /**
     * Wandelt einen Dateipfad in einen vollqualifizierten Klassennamen um.
     */
    private function fileToClassName(string $filePath): string
    {
        $relativePath = str_replace($this->entityPath . '/', '', $filePath);
        $class = str_replace('/', '\\', substr($relativePath, 0, -4)); // .php entfernen
        return $this->entityNamespace . '\\' . $class;
    }

    /**
     * Wandelt einen Klassennamen in einen Tabellennamen um (z.B. User -> users).
     * Kann bei Bedarf angepasst werden.
     */
    private function classToTableName(string $classShortName): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $classShortName)) . 's';
    }
}