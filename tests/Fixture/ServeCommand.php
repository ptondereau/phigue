<?php

declare(strict_types = 1);

namespace Phigue\Tests\Fixture;

use Phigue\Attribute\Named;

final class ServeCommand
{
    public function __construct(
        #[Named]
        public int $workers = 1,
    ) {
    }
}
