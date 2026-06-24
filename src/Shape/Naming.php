<?php

declare(strict_types = 1);

namespace Phigue\Shape;

final readonly class Naming
{
    public static function kebab(string $name): string
    {
        return strtolower((string) preg_replace('/[A-Z]/', '-$0', lcfirst($name)));
    }

    public static function screamingSnake(string $name): string
    {
        return strtoupper((string) preg_replace('/[A-Z]/', '_$0', lcfirst($name)));
    }

    public static function command(string $class): string
    {
        $position = strrpos($class, '\\');
        $short = $position === false ? $class : substr($class, $position + 1);

        if (str_ends_with($short, 'Command')) {
            $short = substr($short, 0, -strlen('Command'));
        }

        return self::kebab($short);
    }
}
