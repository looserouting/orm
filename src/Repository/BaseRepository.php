<?php
namespace Orm\Repository;

use PDO;
use Orm\Entity\BaseEntity;

abstract class BaseRepository
{
    public function __construct(protected PDO $pdo) {}

    abstract public function save(BaseEntity $entity): void;
    abstract public function find(int $id): ?BaseEntity;
}
