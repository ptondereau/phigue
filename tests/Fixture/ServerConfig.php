<?php

declare(strict_types = 1);

namespace Phigue\Tests\Fixture;

use Phigue\Attribute\Named;

final class ServerConfig
{
    public function __construct(
        #[Named(short: 'H')]
        public string $host = '127.0.0.1',
        #[Named(short: 'p')]
        public int $port = 8080,
        public bool $tls = false,
    ) {
    }
}
