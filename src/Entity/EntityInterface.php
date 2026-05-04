<?php

namespace Orm\Entity;

interface EntityInterface
{
    public function toArray(): array;
    public function fromArray(array $data): void;
}
