<?php
namespace Orm\Entity;

abstract class BaseEntity
{

    private ?\ReflectionObject $reflectionObject = null;
    private ?\ReflectionClass $reflectionClass = null;

    protected function getReflectionClass(): \ReflectionClass
    {
        return $this->reflectionClass ??= new \ReflectionClass($this);
    }

    protected function getReflectionObject(): \ReflectionObject
    {
        return $this->reflectionObject ??= new \ReflectionObject($this);
    }

    /**
     * Wandelt die aktuellen Eigenschaften der Entität in ein assoziatives Array um.
     * Nur öffentliche und geschützte Eigenschaften werden berücksichtigt.
     */
    final public function toArray(): array
    {
        $array = [];
        $refObject = $this->getReflectionObject();
        foreach ($refObject->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED) as $property) {
            // Skip properties marked with #[Sensitive]
            if ($property->getAttributes(\App\Attributes\Sensitive::class)) {
                continue;
            }
            $name = $property->getName();
            $array[$name] = $this->$name;
        }
        return $array;
    }

    /**
     * Überträgt Werte aus einem Array auf die entsprechenden Eigenschaften der Entität.
     * Nur Eigenschaften, die existieren, werden gesetzt. Private Eigenschaften werden ignoriert.
     * 
     * @param array $data Assoziatives Array mit Schlüsseln entsprechend den Eigenschaftsnamen der Entität.
     *                    Beispiel: ['id' => int, 'name' => string, ...]
     */
    final public function fromArray(array $data): void
    {
        $refClass = $this->getReflectionClass();
        foreach ($data as $name => $value) {
            if ($refClass->hasProperty($name)) {
                $property = $refClass->getProperty($name);
                // Skip properties marked with #[Sensitive]
                if ($property->getAttributes(\App\Attributes\Sensitive::class)) {
                    continue;
                }
                if ($property->isPublic() || $property->isProtected()) {
                    // Example: basic type and value validation
                    $type = $property->getType();
                    if ($type && !is_null($value)) {
                        $valid = false;
                        if ($type instanceof \ReflectionUnionType) {
                            foreach ($type->getTypes() as $unionType) {
                                $typeName = $unionType->getName();
                                if (
                                    ($typeName === 'int' && is_int($value)) ||
                                    ($typeName === 'string' && is_string($value)) ||
                                    ($typeName === 'float' && is_float($value)) ||
                                    ($typeName === 'bool' && is_bool($value)) ||
                                    ($typeName === 'array' && is_array($value)) ||
                                    ($typeName === 'null' && is_null($value))
                                ) {
                                    $valid = true;
                                    break;
                                }
                            }
                        } elseif ($type instanceof \ReflectionNamedType) {
                            $typeName = $type->getName();
                            if (
                                ($typeName === 'int' && is_int($value)) ||
                                ($typeName === 'string' && is_string($value)) ||
                                ($typeName === 'float' && is_float($value)) ||
                                ($typeName === 'bool' && is_bool($value)) ||
                                ($typeName === 'array' && is_array($value)) ||
                                ($typeName === 'null' && is_null($value))
                            ) {
                                $valid = true;
                            }
                        }
                        if (!$valid) {
                            throw new \InvalidArgumentException("Property `$name` expects {$type}, " . gettype($value) . " given.");
                        }
                    }
                    // Optionally sanitize string values
                    if (is_string($value)) {
                        $value = trim($value);
                    }
                    if (method_exists($this, 'set' . ucfirst($name))) {
                        $setter = 'set' . ucfirst($name);
                        $this->$setter($value);
                    } else {
                        $property->setValue($this, $value);
                    }
                }
            }
        }
    }
}