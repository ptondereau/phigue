<?php

declare(strict_types = 1);

namespace Phigue\Shape;

final readonly class Field
{
    /**
     * @param array<string, class-string> $subcommands
     */
    public function __construct(
        public string $name,
        public string $type,
        public bool $allowsNull,
        public bool $hasDefault,
        public mixed $default,
        public ?string $cliLong = null,
        public ?string $cliShort = null,
        public bool $positional = false,
        public ?int $positionalIndex = null,
        public bool $counted = false,
        public ?string $envName = null,
        public bool $flatten = false,
        public bool $hidden = false,
        public bool $secret = false,
        public ?string $help = null,
        public array $subcommands = [],
    ) {
    }

    public function isScalar(): bool
    {
        return in_array($this->type, ['int', 'float', 'string', 'bool'], true);
    }

    public function isSubcommand(): bool
    {
        return $this->subcommands !== [];
    }
}
