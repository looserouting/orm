<?php
namespace Orm\Collection;

use IteratorAggregate;
use ArrayIterator;

class EntityCollection implements IteratorAggregate
{
    public function __construct(private array $items = []) {}

    public function add(object $entity): void {
        $this->items[] = $entity;
    }

    public function all(): array {
        return $this->items;
    }

    public function getIterator(): \Traversable {
        return new ArrayIterator($this->items);
    }
}
