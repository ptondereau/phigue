<?php

declare(strict_types = 1);

namespace Phigue;

final readonly class PathTree
{
    /**
     * @param array<array-key, mixed> $flat
     * @return array<array-key, mixed>
     */
    public static function expand(array $flat): array
    {
        $tree = [];
        foreach ($flat as $path => $value) {
            $tree = self::insert($tree, explode('.', (string) $path), $value);
        }

        return $tree;
    }

    /**
     * @param array<array-key, mixed> $tree
     * @param list<string> $segments
     * @return array<array-key, mixed>
     */
    private static function insert(array $tree, array $segments, mixed $value): array
    {
        $segment = $segments[0];

        if (count($segments) === 1) {
            $tree[$segment] = $value;

            return $tree;
        }

        $child = $tree[$segment] ?? [];
        if (!is_array($child)) {
            $child = [];
        }

        $tree[$segment] = self::insert($child, array_slice($segments, 1), $value);

        return $tree;
    }
}
