<?php

declare(strict_types = 1);

namespace Phigue\Tests\Fixture;

use Phigue\Attribute\Positional;

final class MigrateCommand
{
    public function __construct(
        #[Positional]
        public string $target,
    ) {
    }
}
