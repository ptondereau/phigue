<?php

declare(strict_types = 1);

namespace PHacet\Tests;

use PHacet\Layered;
use PHacet\Tests\Fixture\CollectingLogger;
use PHacet\Tests\Fixture\NullWritePsr16Cache;
use PHacet\Tests\Fixture\NullWritePsr6Pool;
use PHacet\Tests\Fixture\ServerConfig;
use PHacet\Tests\Fixture\ThrowingPsr16Cache;
use PHacet\Tests\Fixture\ThrowingPsr6Pool;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CacheLoggingTest extends TestCase
{
    #[Test]
    public function it_logs_a_warning_when_the_psr6_backend_throws(): void
    {
        $logger = new CollectingLogger();

        Layered::for(ServerConfig::class)->cache(new ThrowingPsr6Pool(), $logger)->build();

        self::assertCount(1, $logger->records);
        self::assertStringContainsString('falling back to reflection', $logger->records[0]['message']);
        self::assertSame(ServerConfig::class, $logger->records[0]['context']['target']);
    }

    #[Test]
    public function it_logs_a_warning_when_the_psr16_backend_throws(): void
    {
        $logger = new CollectingLogger();

        Layered::for(ServerConfig::class)->cache(new ThrowingPsr16Cache(), $logger)->build();

        self::assertCount(1, $logger->records);
        self::assertStringContainsString('falling back to reflection', $logger->records[0]['message']);
    }

    #[Test]
    public function it_logs_a_warning_when_a_psr6_write_fails_silently(): void
    {
        $logger = new CollectingLogger();

        Layered::for(ServerConfig::class)->cache(new NullWritePsr6Pool(), $logger)->build();

        self::assertCount(1, $logger->records);
        self::assertStringContainsString('recomputed next time', $logger->records[0]['message']);
    }

    #[Test]
    public function it_logs_a_warning_when_a_psr16_write_fails_silently(): void
    {
        $logger = new CollectingLogger();

        Layered::for(ServerConfig::class)->cache(new NullWritePsr16Cache(), $logger)->build();

        self::assertCount(1, $logger->records);
        self::assertStringContainsString('recomputed next time', $logger->records[0]['message']);
    }

    #[Test]
    public function it_does_not_log_on_a_successful_cache_roundtrip(): void
    {
        $logger = new CollectingLogger();

        Layered::for(ServerConfig::class)->cache(new ArrayAdapter(), $logger)->build();

        self::assertSame([], $logger->records);
    }
}
