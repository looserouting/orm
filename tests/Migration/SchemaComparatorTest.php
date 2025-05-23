<?php

use Orm\Migration\SchemaComparator;
use PHPUnit\Framework\TestCase;

class SchemaComparatorTest extends TestCase
{
    public function testCompareDetectsTableDiff()
    {
        $old = ['users'=>['columns'=>[]]];
        $new = ['users'=>['columns'=>[]],'posts'=>['columns'=>[]]];
        $comparator = new SchemaComparator();
        $diff = $comparator->compare($old, $new);
        $this->assertIsArray($diff);
        $this->assertArrayHasKey('up', $diff);
        $this->assertArrayHasKey('down', $diff);
        $this->assertNotEmpty($diff['up']);
    }
}