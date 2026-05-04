<?php

namespace Orm\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public ?string $name = null,
        public ?string $type = null,
        public bool $unique = false,
        public bool $nullable = true,
    ) {
    }
}
