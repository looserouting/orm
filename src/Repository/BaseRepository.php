<?php
namespace Orm\Repository;

use PDO;
use Orm\Entity\BaseEntity;
use Orm\Collection\EntityCollection;

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

    /**
     * Leitet die zugehörige Entity-Klasse anhand des Repository-Klassennamens ab.
     * Beispiel: UserRepository => \Orm\Entity\UserEntity
     */
    protected function resolveEntityClass(): string
    {
        $repoClass = (new \ReflectionClass($this))->getShortName();
        $entityBase = preg_replace('/Repository$/', '', $repoClass);
        return 'Orm\\Entity\\' . $entityBase . 'Entity';
    }

    /**
     * Leitet den Tabellennamen aus dem Entity-Namen ab (snake_case, singular).
     */
    protected function resolveTableName(): string
    {
        $entityShort = (new \ReflectionClass($this->entityClass))->getShortName();
        $entityShort = preg_replace('/Entity$/', '', $entityShort);
        $table = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $entityShort));
        return $table;
    }

    /**
     * Erstellt eine neue Entität in der Datenbank.
     *
     * @param BaseEntity $entity
     * @return void
     */
    public function create(BaseEntity $entity): void
    {
        $data = $entity->toArray();
        unset($data['id']);

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

        if (property_exists($entity, 'id')) {
            $entity->id = $this->pdo->lastInsertId();
        }
    }

    /**
     * Findet eine Entität anhand ihrer ID.
     *
     * @param int $id
     * @return BaseEntity|null
     */
    public function findById(int $id): ?BaseEntity
    {
        $sql = sprintf("SELECT * FROM %s WHERE id = :id", $this->tableName);
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

    /**
     * Aktualisiert eine bestehende Entität.
     *
     * @param BaseEntity $entity
     * @return void
     */
    public function update(BaseEntity $entity): void
    {
        $data = $entity->toArray();
        if (!isset($data['id'])) {
            throw new \InvalidArgumentException('Entity must have an id for update.');
        }
        $id = $data['id'];
        unset($data['id']);

        $set = [];
        foreach ($data as $col => $val) {
            $set[] = "$col = :$col";
        }

        $sql = sprintf(
            "UPDATE %s SET %s WHERE id = :id",
            $this->tableName,
            implode(', ', $set)
        );

        $stmt = $this->pdo->prepare($sql);
        foreach ($data as $col => $val) {
            $stmt->bindValue(':' . $col, $val);
        }
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    }

    /**
     * Löscht eine Entität anhand ihrer ID.
     *
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        $sql = sprintf("DELETE FROM %s WHERE id = :id", $this->tableName);
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
    }

    /**
     * Gibt alle Entities als Collection zurück (optional: mit Limit/Offset).
     *
     * @param int|null $limit
     * @param int|null $offset
     * @return EntityCollection
     */
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
}
