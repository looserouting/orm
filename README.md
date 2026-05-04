# ORM (Antigravity Edition)

A lightweight Object-Relational Mapper (ORM) for PHP 8.1+, focusing on simplicity, naming conventions, and built-in migration support.

## Core Features

- **Reflection-based Mapping**: Automatic property-to-column mapping.
- **Support for Private Properties**: Clean encapsulation with full access for the ORM.
- **Attribute-based Metadata**: Fine-grained control with `#[Id]`, `#[Column]`, and `#[Sensitive]`.
- **Integrated Migrations**: Automatic schema extraction and SQL generation (Up/Down support).
- **Type Validation**: Built-in validation including support for numeric strings from databases.

## Usage

### 1. Define your Entity

Entities should extend `BaseEntity`. Use attributes to define your schema.

```php
use Orm\Entity\BaseEntity;
use Orm\Attribute\Id;
use Orm\Attribute\Column;
use Orm\Attribute\Sensitive;

class UserEntity extends BaseEntity
{
    #[Id]
    private int $id;

    #[Column(unique: true)]
    private string $username;

    #[Column(name: 'email_address')]
    private string $email;

    #[Sensitive]
    private string $password;

    // Getters and setters are supported but optional for the ORM
    public function getId(): int { return $this->id; }
    public function getUsername(): string { return $this->username; }
}
```

*Note: Table names are automatically pluralized (e.g., `UserEntity` -> `userentities` or `User` -> `users`). Use the naming convention `[Name]Entity` or just `[Name]`.*

### 2. Define your Repository

Repositories handle database operations.

```php
use Orm\Repository\BaseRepository;

class UserRepository extends BaseRepository 
{
    // Custom query methods can be added here
}
```

### 3. Database Operations

```php
$pdo = new PDO('sqlite:db.sqlite');
$repo = new UserRepository($pdo);

// Create
$user = new UserEntity();
$user->fromArray([
    'username' => 'johndoe',
    'email_address' => 'john@example.com',
    'password' => 'secret123'
]);
$repo->create($user);

// Find
$foundUser = $repo->findById($user->getId());

// Update
$foundUser->fromArray(['username' => 'john_fixed']);
$repo->update($foundUser);

// Delete
$repo->delete($foundUser->getId());

// Find All
$allUsers = $repo->findAll(limit: 10, offset: 0); // Returns EntityCollection
```

### 4. Migration System

The ORM can automatically manage your database schema.

```bash
# Initialize the schema (creates initial SQL and snapshot)
php bin/migrate.php init

# After changing your Entities, create a new migration
php bin/migrate.php create

# Run pending migrations
php bin/migrate.php up

# Revert migrations
php bin/migrate.php down --to=20231027_120000

# Check status
php bin/migrate.php status
```

## Recommended Composer Scripts

Add these to your `composer.json` for easier access:

```json
{
  "scripts": {
    "migrate": "php bin/migrate.php",
    "create-entity": "php bin/create.php entity"
  }
}
```

## Installation

```bash
composer require looserouting/orm
```