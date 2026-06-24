<?php

declare(strict_types = 1);

namespace Phigue\Tests;

use Phigue\Layered;
use Phigue\Tests\Fixture\ServerConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LayeredTest extends TestCase
{
    #[Test]
    public function it_hydrates_a_typed_object_from_an_array_source(): void
    {
        $config = Layered::for(ServerConfig::class)->values([
            'host' => 'example.com',
            'port' => 9000,
            'tls' => true,
        ])->build();

        self::assertInstanceOf(ServerConfig::class, $config);
        self::assertSame('example.com', $config->host);
        self::assertSame(9000, $config->port);
        self::assertTrue($config->tls);
    }

    #[Test]
    public function it_falls_back_to_constructor_defaults_for_absent_values(): void
    {
        $config = Layered::for(ServerConfig::class)->values([
            'host' => 'example.com',
        ])->build();

        self::assertSame('example.com', $config->host);
        self::assertSame(8080, $config->port);
        self::assertFalse($config->tls);
    }

    #[Test]
    public function a_later_source_overrides_an_earlier_one(): void
    {
        $config = Layered::for(ServerConfig::class)
            ->values(['host' => 'low', 'port' => 1])
            ->values(['port' => 2])
            ->build();

        self::assertSame('low', $config->host);
        self::assertSame(2, $config->port);
    }

    #[Test]
    public function it_reads_and_coerces_env_values_by_prefixed_screaming_snake_key(): void
    {
        $config = Layered::for(ServerConfig::class)->env('APP', [
            'APP_HOST' => 'env-host',
            'APP_PORT' => '9090',
            'APP_TLS' => 'true',
        ])->build();

        self::assertSame('env-host', $config->host);
        self::assertSame(9090, $config->port);
        self::assertTrue($config->tls);
    }
}
