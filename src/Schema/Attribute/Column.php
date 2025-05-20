<?php
namespace Orm\Schema\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column {
    public function __construct(
        public string $name,
        public string $type = 'TEXT',
        public bool $primary = false,
        public bool $nullable = true,
        public bool $autoIncrement = false
    ) {}
}
