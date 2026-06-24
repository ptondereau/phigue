<?php

declare(strict_types = 1);

namespace Phigue\Source;

use Phigue\Shape\Shape;

final readonly class ArraySource implements Source
{
    /** @param array<string, mixed> $values */
    public function __construct(
        private array $values,
    ) {
    }

    public function read(Shape $shape): array
    {
        $result = [];
        foreach ($shape->leaves() as $leaf) {
            if (!array_key_exists($leaf->field->name, $this->values)) {
                continue;
            }

            $result[$leaf->key()] = $this->values[$leaf->field->name];
        }

        return $result;
    }
}
