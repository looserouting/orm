# ORM
ORM for my Micro Framework

# Example

Entity:
use Orm\Entity\BaseEntity;

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

class UserRepository extends BaseRepository {}

Collection:
Transaction:

Migration:
php bin/migration.php init

Mini-Test:
$pdo = new PDO('sqlite::memory:');
$repo = new UserRepository($pdo);
$user = new User();
$user->username = "test";
$repo->save($user);

print_r($repo->find($user->id));


Insert Scripts into composer.json of your project
---
{
  "scripts": {
    "create-entity": "php vendor/looserouting/orm/bin/create.php entity"
    "migrate": "php vendor/looserouting/orm/bin/migrate.php"
  }
}