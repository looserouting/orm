<?php

use PHPUnit\Framework\TestCase;
use Orm\Transaction\TransactionManager;

class TransactionManagerTest extends TestCase
{
    public function testBeginCommit()
    {
        $pdo = new PDO('sqlite::memory:');
        $trans = new TransactionManager($pdo);
        $result = $trans->beginTransaction();
        $this->assertTrue($result);
        $commit = $trans->commitTransaction();
        $this->assertTrue($commit);
    }

    public function testRunExecutesCallableAndCommits()
    {
        $pdo = new PDO('sqlite::memory:');
        $trans = new TransactionManager($pdo);
        $result = $trans->run(fn($pdo)=>42);
        $this->assertEquals(42, $result);
    }

    public function testRunRollsBackOnException()
    {
        $pdo = new PDO('sqlite::memory:');
        $trans = new TransactionManager($pdo);
        $this->expectException(Exception::class);
        $trans->run(function($pdo){ throw new Exception("Fail!"); });
    }
}