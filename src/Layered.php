<?php

declare(strict_types = 1);

namespace Phigue;

use Phigue\Cache\Psr16ShapeCache;
use Phigue\Cache\Psr6ShapeCache;
use Phigue\Cache\ShapeCache;
use Phigue\Help\HelpFormatter;
use Phigue\Shape\Shape;
use Phigue\Source\ArgvSource;
use Phigue\Source\ArraySource;
use Phigue\Source\EnvSource;
use Phigue\Source\FileSource;
use Phigue\Source\Source;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

/**
 * @template T of object
 */
final readonly class Layered
{
    /**
     * @param class-string<T> $target
     * @param list<Source> $sources
     */
    private function __construct(
        private string $target,
        private array $sources,
        private ?ShapeCache $cache = null,
    ) {
    }

    /**
     * @template TFor of object
     * @param class-string<TFor> $target
     * @return self<TFor>
     */
    public static function for(string $target): self
    {
        return new self($target, []);
    }

    /**
     * @return self<T>
     */
    public function source(Source $source): self
    {
        return new self($this->target, [...$this->sources, $source], $this->cache);
    }

    /**
     * Cache the reflection plan so introspection is skipped on later runs.
     *
     * Pass a PSR-3 logger to record backend read and write failures; without
     * one those failures are swallowed and the plan is recomputed.
     *
     * @return self<T>
     */
    public function cache(CacheItemPoolInterface|CacheInterface $cache, ?LoggerInterface $logger = null): self
    {
        $logger ??= new NullLogger();

        $adapter = $cache instanceof CacheItemPoolInterface
            ? new Psr6ShapeCache($cache, $logger)
            : new Psr16ShapeCache($cache, $logger);

        return new self($this->target, $this->sources, $adapter);
    }

    /**
     * @param array<string, mixed> $values
     * @return self<T>
     */
    public function values(array $values): self
    {
        return $this->source(new ArraySource($values));
    }

    /**
     * @param list<string> $paths
     * @return self<T>
     */
    public function files(array $paths): self
    {
        $layered = $this;
        foreach ($paths as $path) {
            $layered = $layered->source(new FileSource($path));
        }

        return $layered;
    }

    /**
     * @param array<string, string>|null $env
     * @return self<T>
     */
    public function env(string $prefix, ?array $env = null): self
    {
        return $this->source(new EnvSource($prefix, $env));
    }

    /**
     * @param list<string> $argv
     * @return self<T>
     */
    public function args(array $argv): self
    {
        return $this->source(new ArgvSource($argv));
    }

    /**
     * @return T
     */
    public function build(): object
    {
        $shape = $this->cache?->shape($this->target) ?? Shape::of($this->target);

        $merged = [];
        foreach ($this->sources as $source) {
            foreach ($source->read($shape) as $path => $value) {
                $merged[$path] = $value;
            }
        }

        /** @var T */
        return ( new Hydrator() )->hydrate($shape, PathTree::expand($merged));
    }

    public function help(): string
    {
        $shape = $this->cache?->shape($this->target) ?? Shape::of($this->target);

        return ( new HelpFormatter() )->format($shape);
    }
}
