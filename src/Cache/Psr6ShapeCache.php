<?php

declare(strict_types = 1);

namespace PHacet\Cache;

use PHacet\Shape\Shape;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class Psr6ShapeCache implements ShapeCache
{
    public function __construct(
        private CacheItemPoolInterface $pool,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * @param class-string $target
     */
    public function shape(string $target): Shape
    {
        try {
            $item = $this->pool->getItem(CacheKey::for($target));
        } catch (CacheException $e) {
            $this->logger->warning('PHacet could not read the cached reflection plan; falling back to reflection.', [
                'target' => $target,
                'exception' => $e,
            ]);

            return Shape::of($target);
        }

        $cached = $item->get();
        if ($item->isHit() && $cached instanceof Shape) {
            return $cached;
        }

        $shape = Shape::of($target);

        try {
            $stored = $this->pool->save($item->set($shape));
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
