<?php

declare(strict_types = 1);

namespace Phigue\Cache;

use Phigue\Shape\Shape;

/**
 * Resolves a target class's reflection plan and caches it across runs.
 *
 * The cache is best-effort. When a read fails, the implementation recomputes
 * the plan by reflection. When a write fails, whether it throws or reports
 * failure through a false return, the implementation returns the plan it
 * already computed and leaves it uncached, so the next call recomputes it.
 * Both paths log a warning through the PSR-3 logger passed to Layered::cache(),
 * and stay silent when none is set.
 */
interface ShapeCache
{
    /**
     * @param class-string $target
     */
    public function shape(string $target): Shape;
}
