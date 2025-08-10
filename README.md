# ORM
ORM for my Micro Framework

# Example

Entity:

```php
use Orm\Entity\BaseEntity;

#[Table('users')]
class User extends BaseEntity
{
    #[Column(name: 'id', type: 'INTEGER', primary: true, autoIncrement: true, nullable: false)]
    public ?int $id = null;

    #[Column(name: 'username', type: 'TEXT', nullable: false)]
    public string $username;
}
```

Repository:
```php
use looserouting\orm\Repository\BaseRepository;

class UserRepository extends BaseRepository {}
```

Collection:

Transaction:

Migration:
```
php bin/migrate.php init
```
Mini-Test:
```php
$pdo = new PDO('sqlite::memory:');
$repo = new UserRepository($pdo);
$user = new User();
$user->username = "test";
$repo->save($user);

print_r($repo->find($user->id));
```

You may want to add this scripts into composer.json of your project
```json
{
  "scripts": {
    "create": "php vendor/looserouting/orm/bin/create.php entity",
    "migrate": "php vendor/looserouting/orm/bin/migrate.php"
  }
}
```