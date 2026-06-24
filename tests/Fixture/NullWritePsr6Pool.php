<?php

declare(strict_types = 1);

namespace PHacet\Tests\Fixture;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class NullWritePsr6Pool implements CacheItemPoolInterface
{
    public function getItem(string $key): CacheItemInterface
    {
        return new FakeCacheItem($key);
    }

    /**
     * @param array<string> $keys
     * @return iterable<string, CacheItemInterface>
     */
    public function getItems(array $keys = []): iterable
    {
        return [];
    }

    public function hasItem(string $key): bool
    {
        return false;
    }

    public function clear(): bool
    {
        return false;
    }

    public function deleteItem(string $key): bool
    {
        return false;
    }

    /**
     * @param array<string> $keys
     */
    public function deleteItems(array $keys): bool
    {
        return false;
    }

    public function save(CacheItemInterface $item): bool
    {
        return false;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return false;
    }

    public function commit(): bool
    {
        return false;
    }
}
