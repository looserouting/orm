<?php
namespace Orm\Transaction;

use PDO;
use Throwable;

class TransactionManager
{
    public function __construct(private PDO $pdo) {}

    /**
     * Startet eine neue Transaktion.
     *
     * @return bool Gibt true zurück, wenn die Transaktion erfolgreich gestartet wurde, andernfalls false.
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commitet die aktuelle Transaktion.
     *
     * @return bool Gibt true zurück, wenn der Commit erfolgreich war, andernfalls false.
     */
    public function commitTransaction(): bool
    {
        return $this->pdo->commit();
    }

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
