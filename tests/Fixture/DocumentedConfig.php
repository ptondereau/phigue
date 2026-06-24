<?php

declare(strict_types = 1);

namespace Phigue\Tests\Fixture;

use Phigue\Attribute\Help;
use Phigue\Attribute\Hidden;
use Phigue\Attribute\Named;
use Phigue\Attribute\Positional;
use Phigue\Attribute\Secret;

final class DocumentedConfig
{
    public function __construct(
        #[Named(short: 'H'), Help('Address to bind to')]
        public string $host = '127.0.0.1',
        #[Secret]
        public string $apiKey = 'sk-default',
        #[Hidden]
        public bool $debug = false,
        #[Positional]
        public ?string $configPath = null,
    ) {
    }
}
