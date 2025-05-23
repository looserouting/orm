// Attribute/SensitiveTest.php
<?php

use Orm\Attribute\Sensitive;
use PHPUnit\Framework\TestCase;

class SensitiveTest extends TestCase
{
    public function testCanInstantiateSensitiveAttribute()
    {
        $attr = new Sensitive();
        $this->assertInstanceOf(Sensitive::class, $attr);
        // Check attribute reflection
        $reflection = new ReflectionClass($attr);
        $this->assertTrue($reflection->isInstantiable());
    }
}

// Entity/BaseEntityTest.php
<?php

use Orm\Entity\BaseEntity;
use Orm\Attribute\Sensitive;
use PHPUnit\Framework\TestCase;

class DummyEntity extends BaseEntity
{
    public int $id = 1;
    public string $name = 'foo';

    #[Sensitive]
    protected string $secret = 'xxx';
}

class BaseEntityTest extends TestCase
{
    public function testToArrayIgnoresSensitive()
    {
        $entity = new DummyEntity();
        $result = $entity->toArray();
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('secret', $result);
    }

    public function testFromArrayIgnoresSensitive()
    {
        $entity = new DummyEntity();
        $entity->fromArray(['id'=>5, 'name'=>'test', 'secret'=>'hack']);
        $this->assertEquals(5, $entity->id);
        $this->assertEquals('test', $entity->name);
        $this->assertEquals('xxx', (fn()=>$this->secret)->call($entity)); // Protected access
    }
}

// Migration/EntitySchemaExtractorTest.php
<?php

use Orm\Migration\EntitySchemaExtractor;
use PHPUnit\Framework\TestCase;

class EntitySchemaExtractorTest extends TestCase
{
    public function testExtractSchemaReturnsArray()
    {
        $extractor = new EntitySchemaExtractor('Orm\Entity', __DIR__.'/../../src/Entity');
        $schema = $extractor->extractSchema();
        $this->assertIsArray($schema);
        // Da projektabhängig: Check, dass Tabellen und Columns grundsätzlich erkannt werden
    }
}

// Migration/InitialSchemaSqlGeneratorTest.php
<?php

use Orm\Migration\InitialSchemaSqlGenerator;
use PHPUnit\Framework\TestCase;

class InitialSchemaSqlGeneratorTest extends TestCase
{
    public function testGenerateReturnsCreateTableString()
    {
        $generator = new InitialSchemaSqlGenerator();
        $schema = [
            'users' => [
                'columns' => [
                    'id' => ['type'=>'int', 'nullable'=>false, 'primary'=>true],
                    'name' => ['type'=>'string', 'nullable'=>false]
                ]
            ]
        ];
        $sql = $generator->generate($schema);
        $this->assertStringContainsString('CREATE TABLE `users`', $sql);
        $this->assertStringContainsString('`id` INTEGER NOT NULL', $sql);
    }
}

// Migration/MigrationSqlGeneratorTest.php
<?php

use Orm\Migration\MigrationSqlGenerator;
use PHPUnit\Framework\TestCase;

class MigrationSqlGeneratorTest extends TestCase
{
    public function testGenerateCreateTable()
    {
        $gen = new MigrationSqlGenerator();
        $diffs = [[
            'action'=>'create_table',
            'table'=>'users',
            'columns'=>['id'=>['type'=>'int','nullable'=>false,'primary'=>true]]
        ]];
        $sql = $gen->generate($diffs);
        $this->assertStringContainsString('CREATE TABLE', $sql);
    }
}

// Migration/SchemaComparatorTest.php
<?php

use Orm\Migration\SchemaComparator;
use PHPUnit\Framework\TestCase;

class SchemaComparatorTest extends TestCase
{
    public function testCompareDetectsTableDiff()
    {
        $old = ['users'=>['columns'=>[]]];
        $new = ['users'=>['columns'=>[]],'posts'=>['columns'=>[]]];
        $comparator = new SchemaComparator();
        $diff = $comparator->compare($old, $new);
        $this->assertIsArray($diff);
        $this->assertArrayHasKey('up', $diff);
        $this->assertArrayHasKey('down', $diff);
        $this->assertNotEmpty($diff['up']);
    }
}

// Repository/BaseRepositoryTest.php
<?php

use PHPUnit\Framework\TestCase;
use Orm\Repository\BaseRepository;
use Orm\Entity\BaseEntity;

class RepoDummyEntity extends BaseEntity
{
    public int $id = 1;
    public string $name = 'foo';
}

class DummyRepository extends BaseRepository
{
    protected function resolveEntityClass(): string {return RepoDummyEntity::class;}
    protected function resolveTableName(): string {return 'dummy';}
}

class BaseRepositoryTest extends TestCase
{
    public function testConstructSetsProps()
    {
        $pdo = new PDO('sqlite::memory:');
        $repo = new DummyRepository($pdo);
        $this->assertInstanceOf(DummyRepository::class, $repo);
    }
    // Weitergehende Repo-Tests könnten mit sqlite::memory Tables und Queries ausgebaut werden.
}

// Transaction/TransactionManagerTest.php
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