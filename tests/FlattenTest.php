<?php

declare(strict_types = 1);

namespace Phigue\Tests;

use Phigue\Layered;
use Phigue\Tests\Fixture\FlatConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FlattenTest extends TestCase
{
    #[Test]
    public function it_addresses_flattened_child_fields_at_the_parent_level_from_an_array(): void
    {
        $config = Layered::for(FlatConfig::class)->values([
            'host' => 'flat-host',
            'port' => 9000,
            'name' => 'service',
        ])->build();

        self::assertSame('service', $config->name);
        self::assertSame('flat-host', $config->server->host);
        self::assertSame(9000, $config->server->port);
        self::assertFalse($config->server->tls);
    }

    #[Test]
    public function it_addresses_flattened_child_fields_from_env(): void
    {
        $config = Layered::for(FlatConfig::class)->env('APP', [
            'APP_HOST' => 'env-host',
            'APP_PORT' => '5432',
        ])->build();

        self::assertSame('env-host', $config->server->host);
        self::assertSame(5432, $config->server->port);
    }

    #[Test]
    public function it_addresses_flattened_child_fields_from_argv(): void
    {
        $config = Layered::for(FlatConfig::class)->args(['--host', 'cli-host', '-p', '7777', '--name=svc'])->build();

        self::assertSame('svc', $config->name);
        self::assertSame('cli-host', $config->server->host);
        self::assertSame(7777, $config->server->port);
    }

    #[Test]
    public function a_later_source_overrides_one_flattened_field_without_dropping_the_others(): void
    {
        $config = Layered::for(FlatConfig::class)
            ->values(['host' => 'from-file', 'port' => 1111])
            ->env('APP', ['APP_PORT' => '2222'])
            ->build();

        self::assertSame('from-file', $config->server->host);
        self::assertSame(2222, $config->server->port);
    }
}
