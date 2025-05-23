<?php
namespace TEST;

use PHPUnit\Framework\TestCase;
use Orm\Repository\BaseRepository;
use Orm\Entity\BaseEntity;

class RepoDummyEntity extends BaseEntity
{
    public int $id = 0;
    public string $name = '';

    public function __construct(array $data = [])
    {
        $this->fromArray($data + ['id' => 0, 'name' => '']);
    }
}

class RepoDummyRepository extends BaseRepository {}

class BaseRepositoryTest extends TestCase
{
    private \PDO $pdo;
    private RepoDummyRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE repo_dummy (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
        $this->repo = new RepoDummyRepository($this->pdo);
    }

    public function testConstructSetsProps()
    {
        $this->assertInstanceOf(RepoDummyRepository::class, $this->repo);
    }

    public function testCreateAndFindById()
    {
        $entity = new RepoDummyEntity(['name' => 'foo']);
        $this->repo->create($entity);

        $this->assertGreaterThan(0, $entity->id);

        $found = $this->repo->findById($entity->id);
        $this->assertInstanceOf(RepoDummyEntity::class, $found);
        $this->assertEquals('foo', $found->name);
    }

    public function testUpdate()
    {
        $entity = new RepoDummyEntity(['name' => 'bar']);
        $this->repo->create($entity);

        $entity->name = 'baz';
        $this->repo->update($entity);

        $found = $this->repo->findById($entity->id);
        $this->assertEquals('baz', $found->name);
    }

    public function testDelete()
    {
        $entity = new RepoDummyEntity(['name' => 'todelete']);
        $this->repo->create($entity);

        $id = $entity->id;
        $this->repo->delete($id);

        $found = $this->repo->findById($id);
        $this->assertNull($found);
    }

    public function testFindAll()
    {
        $this->repo->create(new RepoDummyEntity(['name' => 'eins']));
        $this->repo->create(new RepoDummyEntity(['name' => 'zwei']));
        $this->repo->create(new RepoDummyEntity(['name' => 'drei']));

        $all = $this->repo->findAll();
        $this->assertCount(3, $all);

        $names = array_map(fn($e) => $e->name, iterator_to_array($all));
        $this->assertContains('eins', $names);
        $this->assertContains('zwei', $names);
        $this->assertContains('drei', $names);
    }
}
