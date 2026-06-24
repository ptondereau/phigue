<?php

declare(strict_types = 1);

namespace Phigue\Source;

use Phigue\Shape\Shape;

interface Source
{
    /**
     * @return array<string, mixed> sparse map of dotted leaf path to raw value
     */
    public function read(Shape $shape): array;
}
