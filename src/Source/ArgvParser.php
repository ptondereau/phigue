<?php

declare(strict_types = 1);

namespace Phigue\Source;

use Phigue\Exception\UnknownOption;
use Phigue\Shape\Leaf;
use Phigue\Shape\Shape;

final class ArgvParser
{
    /** @var array<string, Leaf> */
    private array $byLong = [];

    /** @var array<string, Leaf> */
    private array $byShort = [];

    /** @var list<Leaf> */
    private array $positionals = [];

    /** @var array<string, mixed> */
    private array $result = [];

    /** @var array<string, int> */
    private array $counts = [];

    /** @var list<string> */
    private array $bare = [];

    private ?Leaf $subcommand = null;

    public function __construct(Shape $shape)
    {
        foreach ($shape->leaves() as $leaf) {
            if ($leaf->field->isSubcommand()) {
                $this->subcommand = $leaf;
                continue;
            }
            if ($leaf->field->cliLong !== null) {
                $this->byLong[$leaf->field->cliLong] = $leaf;
            }
            if ($leaf->field->cliShort !== null) {
                $this->byShort[$leaf->field->cliShort] = $leaf;
            }
            if ($leaf->field->positional) {
                $this->positionals[] = $leaf;
            }
        }

        usort(
            $this->positionals,
            static fn(Leaf $a, Leaf $b): int => (
                ( $a->field->positionalIndex ?? PHP_INT_MAX ) <=> ( $b->field->positionalIndex ?? PHP_INT_MAX )
            ),
        );
    }

    /**
     * @param list<string> $argv
     * @return array<string, mixed>
     */
    public function parse(array $argv): array
    {
        $count = count($argv);
        $terminated = false;

        for ($i = 0; $i < $count; $i++) {
            $token = $argv[$i];

            if (!$terminated && $token === '--') {
                $terminated = true;
                continue;
            }

            if (!$terminated && str_starts_with($token, '--')) {
                $i = $this->handleLong($token, $argv, $i);
                continue;
            }

            if (!$terminated && strlen($token) > 1 && $token[0] === '-') {
                $i = $this->handleShort($token, $argv, $i);
                continue;
            }

            if ($this->subcommand !== null) {
                $this->selectCommand($token, array_slice($argv, $i + 1));

                break;
            }

            $this->bare[] = $token;
        }

        $this->assignPositionals();
        foreach ($this->counts as $name => $value) {
            $this->result[$name] = $value;
        }

        return $this->result;
    }

    /**
     * @param list<string> $argv
     */
    private function handleLong(string $token, array $argv, int $i): int
    {
        [$name, $inline] = self::split(substr($token, 2));
        $leaf = $this->byLong[$name] ?? null;
        if ($leaf === null) {
            throw new UnknownOption(sprintf('Unknown option "--%s".', $name));
        }

        return $this->apply($leaf, $inline, $argv, $i);
    }

    /**
     * @param list<string> $rest
     */
    private function selectCommand(string $name, array $rest): void
    {
        $leaf = $this->subcommand;
        if ($leaf === null) {
            return;
        }

        $class = $leaf->field->subcommands[$name] ?? null;
        if ($class === null) {
            throw new UnknownOption(sprintf('Unknown command "%s".', $name));
        }

        $this->result[$leaf->key()] = [$name => ( new ArgvParser(Shape::of($class)) )->parse($rest)];
    }

    /**
     * @param list<string> $argv
     */
    private function handleShort(string $token, array $argv, int $i): int
    {
        [$flags, $inline] = self::split(substr($token, 1));

        if ($inline === null && $this->isCountedRun($flags)) {
            $leaf = $this->byShort[$flags[0]];
            $this->counts[$leaf->key()] = ( $this->counts[$leaf->key()] ?? 0 ) + strlen($flags);

            return $i;
        }

        $leaf = $this->byShort[$flags] ?? null;
        if ($leaf === null) {
            throw new UnknownOption(sprintf('Unknown option "-%s".', $flags));
        }

        return $this->apply($leaf, $inline, $argv, $i);
    }

    /**
     * @param list<string> $argv
     */
    private function apply(Leaf $leaf, ?string $inline, array $argv, int $i): int
    {
        $key = $leaf->key();

        if ($leaf->field->counted) {
            $this->counts[$key] = ( $this->counts[$key] ?? 0 ) + 1;

            return $i;
        }

        if ($inline !== null) {
            $this->result[$key] = $inline;

            return $i;
        }

        if ($leaf->field->type === 'bool') {
            $this->result[$key] = true;

            return $i;
        }

        if (array_key_exists($i + 1, $argv)) {
            $this->result[$key] = $argv[$i + 1];

            return $i + 1;
        }

        $this->result[$key] = '';

        return $i;
    }

    private function assignPositionals(): void
    {
        foreach ($this->positionals as $index => $leaf) {
            if (!array_key_exists($index, $this->bare)) {
                continue;
            }

            $this->result[$leaf->key()] = $this->bare[$index];
        }
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private static function split(string $body): array
    {
        $position = strpos($body, '=');
        if ($position === false) {
            return [$body, null];
        }

        return [substr($body, 0, $position), substr($body, $position + 1)];
    }

    private function isCountedRun(string $flags): bool
    {
        if ($flags === '') {
            return false;
        }

        $first = $flags[0];
        if (str_repeat($first, strlen($flags)) !== $flags) {
            return false;
        }

        return ( $this->byShort[$first] ?? null )?->field->counted === true;
    }
}
