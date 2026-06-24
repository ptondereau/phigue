<?php

declare(strict_types = 1);

namespace PHacet\Cache;

use PHacet\Shape\Shape;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;

final readonly class Psr16ShapeCache implements ShapeCache
{
    public function __construct(
        private CacheInterface $cache,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * @param class-string $target
     */
    public function shape(string $target): Shape
    {
        $key = CacheKey::for($target);

        try {
            $cached = $this->cache->get($key);
        } catch (CacheException $e) {
            $this->logger->warning('PHacet could not read the cached reflection plan; falling back to reflection.', [
                'target' => $target,
                'exception' => $e,
            ]);

            return Shape::of($target);
        }

        if ($cached instanceof Shape) {
            return $cached;
        }

        $shape = Shape::of($target);

        try {
            $stored = $this->cache->set($key, $shape);
        } catch (CacheException $e) {
            $this->logger->warning('PHacet could not write the reflection plan to the cache; it will be recomputed next time.', [
                'target' => $target,
                'exception' => $e,
            ]);

            return $shape;
        }

        if ($stored === false) {
            $this->logger->warning('PHacet could not write the reflection plan to the cache; it will be recomputed next time.', [
                'target' => $target,
            ]);
        }

        return $shape;
    }
}
