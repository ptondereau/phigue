<?php

declare(strict_types = 1);

namespace Phigue\Exception;

use RuntimeException;

class MappingError extends RuntimeException implements PhigueError
{
    /** @param list<MappingError> $failures */
    public function __construct(
        string $message,
        public readonly array $failures = [],
    ) {
        parent::__construct($message);
    }

    public function report(): string
    {
        if ($this->failures === []) {
            return $this->getMessage();
        }

        $lines = array_map(
            static fn(MappingError $failure): string => '  - ' . $failure->getMessage(),
            $this->failures,
        );

        return $this->getMessage() . "\n" . implode("\n", $lines);
    }
}
