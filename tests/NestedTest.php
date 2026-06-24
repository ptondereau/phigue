<?php

declare(strict_types = 1);

namespace Phigue\Tests;

use Phigue\Layered;
use Phigue\Tests\Fixture\AppConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NestedTest extends TestCase
{
    #[Test]
    public function it_hydrates_a_nested_object_from_a_nested_array(): void
    {
        $config = Layered::for(AppConfig::class)->values([
            'server' => ['host' => 'nested-host', 'port' => 1234],
            'name' => 'service',
        ])->build();

        self::assertSame('service', $config->name);
        self::assertSame('nested-host', $config->server->host);
        self::assertSame(1234, $config->server->port);
    }

    #[Test]
    public function it_keeps_the_nested_default_when_absent(): void
    {
        $config = Layered::for(AppConfig::class)->values([
            'name' => 'service',
        ])->build();

        self::assertSame('127.0.0.1', $config->server->host);
    }
}
