# ORM
ORM for my Micro Framework

# Example

Entity:
use Orm\Entity\BaseEntity;
use Orm\Schema\Attribute\Table;
use Orm\Schema\Attribute\Column;

#[Table('users')]
class User extends BaseEntity
{
    #[Column(name: 'id', type: 'INTEGER', primary: true, autoIncrement: true, nullable: false)]
    public ?int $id = null;

    #[Column(name: 'username', type: 'TEXT', nullable: false)]
    public string $username;
}

Repository:
use Orm\Repository\BaseRepository;

class UserRepository extends BaseRepository
{
    public function save(BaseEntity $entity): void {
        /** @var User $entity */
        $stmt = $this->pdo->prepare("INSERT INTO users (username) VALUES (:username)");
        $stmt->execute(['username' => $entity->username]);
        $entity->id = $this->pdo->lastInsertId();
    }

    public function find(int $id): ?User {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $user = new User();
        $user->id = $row['id'];
        $user->username = $row['username'];
        return $user;
    }
}


Mini-Test:
$orm = new Orm(new PDO('sqlite::memory:'));
$sql = $orm->schemaGenerator->generateSQL(User::class);
$orm->pdo->exec($sql);

$repo = new UserRepository($orm->pdo);
$user = new User();
$user->username = "test";
$repo->save($user);

print_r($repo->find($user->id));
