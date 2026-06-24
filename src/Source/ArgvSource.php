<?php

declare(strict_types = 1);

namespace Phigue\Source;

use Phigue\Shape\Shape;

final readonly class ArgvSource implements Source
{
    /** @param list<string> $argv */
    public function __construct(
        private array $argv,
    ) {
    }

    public function read(Shape $shape): array
    {
        return ( new ArgvParser($shape) )->parse($this->argv);
    }
}
