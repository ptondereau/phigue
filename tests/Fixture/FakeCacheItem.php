<?php

declare(strict_types = 1);

namespace PHacet\Tests\Fixture;

use DateInterval;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;

final class FakeCacheItem implements CacheItemInterface
{
    private mixed $value = null;

    public function __construct(
        private readonly string $key,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return false;
    }

    public function set(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function expiresAt(?DateTimeInterface $expiration): static
    {
        return $this;
    }

    public function expiresAfter(int|DateInterval|null $time): static
    {
        return $this;
    }
}
