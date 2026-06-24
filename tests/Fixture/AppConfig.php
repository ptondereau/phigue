<?php

declare(strict_types = 1);

namespace Phigue\Tests\Fixture;

final class AppConfig
{
    public function __construct(
        public ServerConfig $server = new ServerConfig(),
        public string $name = 'app',
    ) {
    }
}
