<?php

use Orm\Attribute\Sensitive;
use PHPUnit\Framework\TestCase;

class SensitiveTest extends TestCase
{
    public function testCanInstantiateSensitiveAttribute()
    {
        $attr = new Sensitive();
        $this->assertInstanceOf(Sensitive::class, $attr);
        // Check attribute reflection
        $reflection = new ReflectionClass($attr);
        $this->assertTrue($reflection->isInstantiable());
    }
}