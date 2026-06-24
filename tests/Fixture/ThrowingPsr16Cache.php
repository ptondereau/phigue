<?php

declare(strict_types = 1);

namespace Phigue\Tests\Fixture;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

final class ThrowingPsr16Cache implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        throw new BackendDown('psr-16 backend is down');
    }

    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        throw new BackendDown('psr-16 backend is down');
    }

    public function delete(string $key): bool
    {
        return false;
    }

    public function clear(): bool
    {
        return false;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return [];
    }

    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        return false;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return false;
    }

    public function has(string $key): bool
    {
        return false;
    }
}
