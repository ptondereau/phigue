<?php

declare(strict_types = 1);

namespace Phigue\Source;

use Phigue\Exception\MappingError;
use Phigue\Shape\Shape;

final readonly class FileSource implements Source
{
    public function __construct(
        private string $path,
    ) {
    }

    public function read(Shape $shape): array
    {
        if (!is_file($this->path)) {
            return [];
        }

        $raw = file_get_contents($this->path);
        if ($raw === false) {
            return [];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new MappingError(sprintf('Config file "%s" does not decode to an object.', $this->path));
        }

        $result = [];
        foreach ($shape->leaves() as $leaf) {
            if (!array_key_exists($leaf->field->name, $data)) {
                continue;
            }

            $result[$leaf->key()] = $data[$leaf->field->name];
        }

        return $result;
    }
}
