<?php
namespace Orm\Transaction;

use PDO;
use Throwable;

class TransactionManager
{
    public function __construct(private PDO $pdo) {}

    public function run(callable $callback): mixed {
        try {
            $this->pdo->beginTransaction();
            $result = $callback($this->pdo);
            $this->pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
