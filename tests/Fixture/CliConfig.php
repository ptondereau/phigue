<?php

declare(strict_types = 1);

namespace Phigue\Tests\Fixture;

use Phigue\Attribute\Counted;
use Phigue\Attribute\Named;
use Phigue\Attribute\Positional;

final class CliConfig
{
    public function __construct(
        #[Named(short: 'v'), Counted]
        public int $verbosity = 0,
        #[Positional]
        public ?string $input = null,
    ) {
    }
}
