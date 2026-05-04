<?php
namespace Orm\Repository;

use PDO;
use Orm\Entity\BaseEntity;
use Orm\Collection\EntityCollection;
use Orm\Attribute\Id;
use Orm\Attribute\Column;
use ReflectionClass;
use ReflectionProperty;

abstract class BaseRepository
{
    protected PDO $pdo;
    protected string $tableName;
    protected string $entityClass;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->entityClass = $this->resolveEntityClass();
        $this->tableName = $this->resolveTableName();
    }

    protected function resolveEntityClass(): string
    {
        $repoClass = get_class($this);
        $entityClass = str_replace('\\Repository\\', '\\Entity\\', $repoClass);
        return preg_replace('/Repository$/', 'Entity', $entityClass);
    }

    protected function resolveTableName(): string
    {
        $reflection = new ReflectionClass($this->entityClass);
        $entityShort = $reflection->getShortName();
        $entityShort = preg_replace('/Entity$/', '', $entityShort);
        $table = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $entityShort));
        // Consistently use plural
        return $table . 's';
    }

    protected function getPrimaryKeyName(): string
    {
        $reflection = new ReflectionClass($this->entityClass);
        foreach ($reflection->getProperties() as $prop) {
            if ($prop->getAttributes(Id::class)) {
                return $prop->getName();
            }
        }
        return 'id'; // Default
    }

    public function create(BaseEntity $entity): void
    {
        $data = $entity->toArray();
        $pk = $this->getPrimaryKeyName();
        unset($data[$pk]);

        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->tableName,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->pdo->prepare($sql);
        foreach ($data as $col => $val) {
            $stmt->bindValue(':' . $col, $val);
        }
        $stmt->execute();

        $id = $this->pdo->lastInsertId();
        
        $reflection = new ReflectionClass($entity);
        if ($reflection->hasProperty($pk)) {
            $prop = $reflection->getProperty($pk);
            $prop->setAccessible(true);
            $prop->setValue($entity, (int)$id);
        }
    }

    public function findById(mixed $id): ?BaseEntity
    {
        $pk = $this->getPrimaryKeyName();
        $sql = sprintf("SELECT * FROM %s WHERE %s = :id", $this->tableName, $pk);
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        $entity = new $this->entityClass();
        $entity->fromArray($data);
        return $entity;
    }

    public function update(BaseEntity $entity): void
    {
        $data = $entity->toArray();
        $pk = $this->getPrimaryKeyName();
        
        if (!isset($data[$pk])) {
            throw new \InvalidArgumentException("Entity must have a primary key ($pk) for update.");
        }
        $id = $data[$pk];
        unset($data[$pk]);

        $set = [];
        foreach ($data as $col => $val) {
            $set[] = "$col = :$col";
        }

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = :id",
            $this->tableName,
            implode(', ', $set),
            $pk
        );

        $stmt = $this->pdo->prepare($sql);
        foreach ($data as $col => $val) {
            $stmt->bindValue(':' . $col, $val);
        }
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    }

    public function delete(mixed $id): void
    {
        $pk = $this->getPrimaryKeyName();
        $sql = sprintf("DELETE FROM %s WHERE %s = :id", $this->tableName, $pk);
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    }

    public function findAll(int $limit = null, int $offset = null): EntityCollection
    {
        $sql = sprintf("SELECT * FROM %s", $this->tableName);
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }
        if ($offset !== null) {
            $sql .= " OFFSET " . (int)$offset;
        }
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $entities = [];
        foreach ($rows as $row) {
            $entity = new $this->entityClass();
            $entity->fromArray($row);
            $entities[] = $entity;
        }
        return new EntityCollection($entities);
    }

    public function findOneBy(string $property, $value): ?BaseEntity
    {
        $sql = sprintf("SELECT * FROM %s WHERE %s = :value LIMIT 1", $this->tableName, $property);
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':value', $value);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        $entity = new $this->entityClass();
        $entity->fromArray($data);
        return $entity;
    }
}
