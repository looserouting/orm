<?php

use Orm\Migration\InitialSchemaSqlGenerator;
use PHPUnit\Framework\TestCase;

class InitialSchemaSqlGeneratorTest extends TestCase
{
    public function testGenerateReturnsCreateTableString()
    {
        $generator = new InitialSchemaSqlGenerator();
        $schema = [
            'users' => [
                'columns' => [
                    'id' => ['type'=>'int', 'nullable'=>false, 'primary'=>true],
                    'name' => ['type'=>'string', 'nullable'=>false]
                ]
            ]
        ];
        $sql = $generator->generate($schema);
        $this->assertStringContainsString('CREATE TABLE `users`', $sql);
        $this->assertStringContainsString('`id` INTEGER NOT NULL', $sql);
    }
}
