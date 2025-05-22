<?php

namespace Orm\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Sensitive
{
    // This attribute marks entity properties as sensitive (e.g., for exclusion from serialization).
}