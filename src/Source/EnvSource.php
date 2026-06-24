<?php

declare(strict_types = 1);

namespace Phigue\Source;

use Phigue\Shape\Shape;

final readonly class EnvSource implements Source
{
    /** @param array<string, string>|null $env */
    public function __construct(
        private string $prefix,
        private ?array $env = null,
    ) {
    }

    public function read(Shape $shape): array
    {
        $env = $this->env ?? getenv();
        $separator = $this->prefix === '' ? '' : $this->prefix . '_';

        $result = [];
        foreach ($shape->leaves() as $leaf) {
            if ($leaf->field->envName === null) {
                continue;
            }

            $key = $separator . $leaf->field->envName;
            if (array_key_exists($key, $env)) {
                $result[$leaf->key()] = $env[$key];
            }
        }

        return $result;
    }
}
