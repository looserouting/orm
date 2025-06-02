<?php
namespace Orm\Collection;

use IteratorAggregate;
use ArrayIterator;
use Countable;

/**
 * Collection of entity objects.
 *
 * @package Orm\Collection
 */
class EntityCollection implements IteratorAggregate, Countable
{
    /**
     * @param MyEntityInterface[] $items
     */
    public function __construct(protected array $items = []) {}

    /**
     * Add an entity to the collection.
     *
     * @param MyEntityInterface $entity
     * @return void
     */
    public function add(MyEntityInterface $entity): void {
        $this->items[] = $entity;
    }

    /**
     * Get all entities in the collection.
     *
     * @return MyEntityInterface[]
     */
    public function all(): array {
        return $this->items;
    }

    /**
     * Get all entities in the collection as an array.
     *
     * @return MyEntityInterface[]
     */
    public function toArray(): array {
        return $this->all();
    }

    /**
     * Get the number of entities in the collection.
     *
     * @return int
     */
    public function count(): int {
        return count($this->items);
    }

    /**
     * Retrieve an external iterator.
     *
     * @return \Traversable
     */
    public function getIterator(): \Traversable {
        return new ArrayIterator($this->items);
    }
}