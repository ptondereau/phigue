<?php

declare(strict_types = 1);

namespace Phigue\Tests\Fixture;

final class LoggingConfig
{
    public function __construct(
        public LogLevel $level = LogLevel::Info,
    ) {
    }
}
