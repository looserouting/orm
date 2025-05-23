<?php

use Orm\Migration\MigrationSqlGenerator;
use PHPUnit\Framework\TestCase;

class MigrationSqlGeneratorTest extends TestCase
{
    public function testGenerateCreateTable()
    {
        $gen = new MigrationSqlGenerator();
        $diffs = [[
            'action'=>'create_table',
            'table'=>'users',
            'columns'=>['id'=>['type'=>'int','nullable'=>false,'primary'=>true]]
        ]];
        $sql = $gen->generate($diffs);
        $this->assertStringContainsString('CREATE TABLE', $sql);
    }
}