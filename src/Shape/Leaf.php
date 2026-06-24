<?php

declare(strict_types = 1);

namespace Phigue\Shape;

final readonly class Leaf
{
    /** @param list<string> $path */
    public function __construct(
        public array $path,
        public Field $field,
    ) {
    }

    public function key(): string
    {
        return implode('.', $this->path);
    }
}
