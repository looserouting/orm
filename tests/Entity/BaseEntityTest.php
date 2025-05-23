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