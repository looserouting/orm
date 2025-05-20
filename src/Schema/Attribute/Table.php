<?php
namespace Orm\Schema\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Table {
    public function __construct(public string $name) {}
}
