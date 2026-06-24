<?php

declare(strict_types = 1);

namespace Phigue\Tests\Fixture;

use Phigue\Attribute\Counted;
use Phigue\Attribute\Named;
use Phigue\Attribute\Subcommand;

final class Cli
{
    public function __construct(
        #[Subcommand]
        public ServeCommand|MigrateCommand $command,
        #[Named(short: 'v'), Counted]
        public int $verbosity = 0,
    ) {
    }
}
