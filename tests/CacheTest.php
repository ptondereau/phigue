<?php

declare(strict_types = 1);

namespace Phigue\Tests;

use Phigue\Cache\CacheKey;
use Phigue\Layered;
use Phigue\Shape\Shape;
use Phigue\Tests\Fixture\NullWritePsr16Cache;
use Phigue\Tests\Fixture\NullWritePsr6Pool;
use Phigue\Tests\Fixture\ServerConfig;
use Phigue\Tests\Fixture\ThrowingPsr16Cache;
use Phigue\Tests\Fixture\ThrowingPsr6Pool;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

final class CacheTest extends TestCase
{
    #[Test]
    public function it_hydrates_through_a_psr6_pool(): void
    {
        $pool = new ArrayAdapter();

        $config = Layered::for(ServerConfig::class)
            ->cache($pool)
            ->values(['host' => 'example.com', 'port' => 9000])
            ->build();

        self::assertSame('example.com', $config->host);
        self::assertSame(9000, $config->port);
    }

    #[Test]
    public function it_hydrates_through_a_psr16_cache(): void
    {
        $cache = new Psr16Cache(new ArrayAdapter());

        $config = Layered::for(ServerConfig::class)
            ->cache($cache)
            ->values(['host' => 'example.com', 'port' => 9000])
            ->build();

        self::assertSame('example.com', $config->host);
        self::assertSame(9000, $config->port);
    }

    #[Test]
    public function it_stores_the_shape_under_the_computed_key(): void
    {
        $pool = new ArrayAdapter();

        Layered::for(ServerConfig::class)->cache($pool)->build();

        $item = $pool->getItem(CacheKey::for(ServerConfig::class));
        self::assertTrue($item->isHit());
        self::assertInstanceOf(Shape::class, $item->get());
    }

    #[Test]
    public function it_reuses_the_cached_shape_on_later_builds(): void
    {
        $pool = new ArrayAdapter(storeSerialized: false);
        $key = CacheKey::for(ServerConfig::class);

        Layered::for(ServerConfig::class)->cache($pool)->build();

        $sentinel = Shape::of(ServerConfig::class);
        $item = $pool->getItem($key)->set($sentinel);
        $pool->save($item);

        $config = Layered::for(ServerConfig::class)
            ->cache($pool)
            ->values(['host' => 'cached', 'port' => 1234])
            ->build();

        self::assertSame($sentinel, $pool->getItem($key)->get());
        self::assertSame('cached', $config->host);
        self::assertSame(1234, $config->port);
    }

    #[Test]
    public function the_key_is_stable_for_the_same_target(): void
    {
        self::assertSame(CacheKey::for(ServerConfig::class), CacheKey::for(ServerConfig::class));
    }

    #[Test]
    public function it_builds_a_stable_key_when_the_class_has_no_source_file(): void
    {
        self::assertStringStartsWith('phigue.shape.', CacheKey::for(\stdClass::class));
        self::assertSame(CacheKey::for(\stdClass::class), CacheKey::for(\stdClass::class));
    }

    #[Test]
    public function it_falls_back_to_reflection_when_the_psr6_backend_throws(): void
    {
        $config = Layered::for(ServerConfig::class)
            ->cache(new ThrowingPsr6Pool())
            ->values(['host' => 'example.com', 'port' => 9000])
            ->build();

        self::assertSame('example.com', $config->host);
        self::assertSame(9000, $config->port);
    }

    #[Test]
    public function it_falls_back_to_reflection_when_the_psr16_backend_throws(): void
    {
        $config = Layered::for(ServerConfig::class)
            ->cache(new ThrowingPsr16Cache())
            ->values(['host' => 'example.com', 'port' => 9000])
            ->build();

        self::assertSame('example.com', $config->host);
        self::assertSame(9000, $config->port);
    }

    #[Test]
    public function it_still_hydrates_when_a_psr6_write_returns_false(): void
    {
        $config = Layered::for(ServerConfig::class)
            ->cache(new NullWritePsr6Pool())
            ->values(['host' => 'example.com', 'port' => 9000])
            ->build();

        self::assertSame('example.com', $config->host);
        self::assertSame(9000, $config->port);
    }

    #[Test]
    public function it_still_hydrates_when_a_psr16_write_returns_false(): void
    {
        $config = Layered::for(ServerConfig::class)
            ->cache(new NullWritePsr16Cache())
            ->values(['host' => 'example.com', 'port' => 9000])
            ->build();

        self::assertSame('example.com', $config->host);
        self::assertSame(9000, $config->port);
    }
}
