<?php

use Orm\Collection\EntityCollection;
use Orm\Entity\BaseEntity;
use PHPUnit\Framework\TestCase;

class CollectionDummyEntity extends BaseEntity {}

class EntityCollectionTest extends TestCase
{
    public function testConstructAndAll()
    {
        $item1 = new CollectionDummyEntity();
        $item2 = new CollectionDummyEntity();
        $col = new EntityCollection([$item1, $item2]);
        $this->assertSame([$item1, $item2], $col->all());
    }

    public function testAdd()
    {
        $col = new EntityCollection();
        $entity = new CollectionDummyEntity();
        $col->add($entity);
        $this->assertSame([$entity], $col->all());
    }

    public function testToArray()
    {
        $entity = new CollectionDummyEntity();
        $col = new EntityCollection([$entity]);
        $this->assertSame([$entity], $col->toArray());
    }

    public function testCount()
    {
        $entity1 = new CollectionDummyEntity();
        $entity2 = new CollectionDummyEntity();
        $col = new EntityCollection([$entity1]);
        $col->add($entity2);
        $this->assertCount(2, $col);
        $this->assertEquals(2, $col->count());
    }

    public function testGetIterator()
    {
        $entity1 = new CollectionDummyEntity();
        $entity2 = new CollectionDummyEntity();
        $col = new EntityCollection([$entity1, $entity2]);
        $items = [];
        foreach ($col as $item) {
            $items[] = $item;
        }
        $this->assertSame([$entity1, $entity2], $items);
    }
}