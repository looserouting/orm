<?php
namespace Orm\Collection;

use IteratorAggregate;
use ArrayIterator;
use Countable;

class EntityCollection implements IteratorAggregate, Countable
{
    public function __construct(private array $items = []) {}

    public function add(object $entity): void {
        $this->items[] = $entity;
    }

    public function all(): array {
        return $this->items;
    }
    
    public function toArray(): array {
        return $this->items;
    }
    
    public function count(): int {
        return count($this->items);
    }

    public function getIterator(): \Traversable {
        return new ArrayIterator($this->items);
    }
}
