<?php

use Orm\Migration\EntitySchemaExtractor;
use PHPUnit\Framework\TestCase;

class EntitySchemaExtractorTest extends TestCase
{
    public function testExtractSchemaReturnsArray()
    {
        $extractor = new EntitySchemaExtractor('Orm\Entity', __DIR__.'/../../src/Entity');
        $schema = $extractor->extractSchema();
        $this->assertIsArray($schema);
        // Da projektabhängig: Check, dass Tabellen und Columns grundsätzlich erkannt werden
    }
}
