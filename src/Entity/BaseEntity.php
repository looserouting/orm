<?php
namespace Orm\Entity;

use Orm\Attribute\Sensitive;
use Orm\Attribute\Id;
use Orm\Attribute\Column;
use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;
use ReflectionNamedType;
use ReflectionUnionType;
use InvalidArgumentException;

abstract class BaseEntity implements EntityInterface
{
    private ?ReflectionObject $reflectionObject = null;
    private ?ReflectionClass $reflectionClass = null;

    private static array $propertiesCache = [];

    protected function getReflectionClass(): ReflectionClass
    {
        return $this->reflectionClass ??= new ReflectionClass($this);
    }

    protected function getReflectionObject(): ReflectionObject
    {
        return $this->reflectionObject ??= new ReflectionObject($this);
    }

    /**
     * Returns all relevant properties of the entity (including private ones).
     */
    protected function getMappedProperties(): array
    {
        $class = static::class;
        if (!isset(self::$propertiesCache[$class])) {
            $refObject = $this->getReflectionObject();
            $props = [];
            foreach ($refObject->getProperties() as $property) {
                // Skip internal ORM properties
                if ($property->getDeclaringClass()->getName() === self::class) {
                    continue;
                }
                $props[] = $property;
            }
            self::$propertiesCache[$class] = $props;
        }
        return self::$propertiesCache[$class];
    }

    /**
     * Converts the entity to an associative array.
     */
    final public function toArray(): array
    {
        $array = [];
        foreach ($this->getMappedProperties() as $property) {
            if ($property->getAttributes(Sensitive::class)) {
                continue;
            }

            $property->setAccessible(true);
            $name = $property->getName();
            
            // Check for Column attribute for name override
            $colAttr = $property->getAttributes(Column::class);
            if (!empty($colAttr)) {
                $col = $colAttr[0]->newInstance();
                if ($col->name) {
                    $name = $col->name;
                }
            }

            $array[$name] = $property->getValue($this);
        }
        return $array;
    }

    /**
     * Maps an array to the entity properties.
     */
    final public function fromArray(array $data): void
    {
        $refClass = $this->getReflectionClass();
        $mappedProps = $this->getMappedProperties();
        
        // Create a lookup map for column name -> property
        $columnMap = [];
        foreach ($mappedProps as $prop) {
            $colName = $prop->getName();
            $colAttr = $prop->getAttributes(Column::class);
            if (!empty($colAttr)) {
                $col = $colAttr[0]->newInstance();
                if ($col->name) {
                    $colName = $col->name;
                }
            }
            $columnMap[$colName] = $prop;
        }

        foreach ($data as $name => $value) {
            if (isset($columnMap[$name])) {
                $property = $columnMap[$name];
                
                if ($property->getAttributes(Sensitive::class)) {
                    continue;
                }

                $type = $property->getType();
                if ($type && !is_null($value)) {
                    if (!$this->isValidType($type, $value)) {
                        throw new InvalidArgumentException("Property `{$property->getName()}` (mapped from `$name`) expects {$type}, " . gettype($value) . " given.");
                    }
                }

                if (is_string($value)) {
                    $value = trim($value);
                }

                $setter = 'set' . ucfirst($property->getName());
                if (method_exists($this, $setter)) {
                    $this->$setter($value);
                } else {
                    $property->setAccessible(true);
                    $property->setValue($this, $value);
                }
            }
        }
    }

    private function isValidType(\ReflectionType $type, $value): bool
    {
        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if ($this->isValidType($unionType, $value)) {
                    return true;
                }
            }
            return false;
        }
        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();
            if ($type->isBuiltin()) {
                return match ($typeName) {
                    'int' => is_int($value) || (is_string($value) && is_numeric($value) && (int)$value == $value),
                    'string' => is_string($value),
                    'float' => is_float($value) || (is_string($value) && is_numeric($value)),
                    'bool' => is_bool($value) || $value === 0 || $value === 1 || $value === '0' || $value === '1',
                    'array' => is_array($value),
                    'null' => is_null($value),
                    default => true,
                };
            } else {
                return $value instanceof $typeName;
            }
        }
        return true;
    }
}