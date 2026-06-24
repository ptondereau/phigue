<?php

declare(strict_types = 1);

namespace Phigue\Cache;

use ReflectionClass;

/**
 * Builds the cache key for a target class's reflection plan.
 *
 * The key combines the class name with the source file's modification time, so
 * editing the class invalidates a stored plan on the next run.
 *
 * When the mtime can't be read, an internal class with no file, a stat race, or
 * restricted permissions, the key falls back to the class name alone and stops
 * auto-invalidating on content changes. Clear the pool on deploy in that case.
 * Classes loaded from a PHAR are stamped with the inner file's manifest
 * timestamp, which build tools normally refresh on each rebuild.
 */
final class CacheKey
{
    /**
     * @param class-string $target
     */
    public static function for(string $target): string
    {
        $file = ( new ReflectionClass($target) )->getFileName();

        $mtime = $file !== false && is_file($file) ? filemtime($file) : false;
        $stamp = $mtime !== false ? (string) $mtime : '';

        return 'phigue.shape.' . sha1($target . '@' . $stamp);
    }
}
