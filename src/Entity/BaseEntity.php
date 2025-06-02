<?php
namespace Orm\Entity;

abstract class BaseEntity
{

    private ?\ReflectionObject $reflectionObject = null;
    private ?\ReflectionClass $reflectionClass = null;

    private static array $nonSensitivePropertiesCache = [];

    protected function getReflectionClass(): \ReflectionClass
    {
        return $this->reflectionClass ??= new \ReflectionClass($this);
    }

    protected function getReflectionObject(): \ReflectionObject
    {
        return $this->reflectionObject ??= new \ReflectionObject($this);
    }

    /**
     * Gibt die nicht-sensitiven öffentlichen und geschützten Eigenschaften der Entität zurück.
     */
    protected function getNonSensitiveProperties(): array
    {
        $class = static::class;
        if (!isset(self::$nonSensitivePropertiesCache[$class])) {
            $refObject = $this->getReflectionObject();
            $props = [];
            foreach ($refObject->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED) as $property) {
                if (!$property->getAttributes(\Orm\Attribute\Sensitive::class)) {
                    $props[] = $property;
                }
            }
            self::$nonSensitivePropertiesCache[$class] = $props;
        }
        return self::$nonSensitivePropertiesCache[$class];
    }

    /**
     * Wandelt die aktuellen Eigenschaften der Entität in ein assoziatives Array um.
     * Nur öffentliche und geschützte Eigenschaften werden berücksichtigt.
     */
    final public function toArray(): array
    {
        $array = [];
        foreach ($this->getNonSensitiveProperties() as $property) {
            $name = $property->getName();
            $array[$name] = $property->getValue($this);
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
                if ($property->getAttributes(\Orm\Attribute\Sensitive::class)) {
                    continue;
                }
                if ($property->isPublic() || $property->isProtected()) {
                    $type = $property->getType();
                    if ($type && !is_null($value)) {
                        if (!$this->isValidType($type, $value)) {
                            throw new \InvalidArgumentException("Property `$name` expects {$type}, " . gettype($value) . " given.");
                        }
                    }
                    // Optionally sanitize string values
                    if (is_string($value)) {
                        $value = trim($value);
                    }
                    $setter = 'set' . ucfirst($name);
                    if (method_exists($this, $setter)) {
                        $this->$setter($value);
                    } else {
                        $property->setValue($this, $value);
                    }
                }
            }
        }
    }

    /**
     * Validiert den Typ eines Wertes gegen den erwarteten ReflectionType.
     *
     * @param \ReflectionType $type
     * @param mixed $value
     * @return bool
     */
    private function isValidType(\ReflectionType $type, $value): bool
    {
        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if ($this->isValidType($unionType, $value)) {
                    return true;
                }
            }
            return false;
        }
        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();
            if ($type->isBuiltin()) {
                return match ($typeName) {
                    'int' => is_int($value),
                    'string' => is_string($value),
                    'float' => is_float($value),
                    'bool' => is_bool($value),
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