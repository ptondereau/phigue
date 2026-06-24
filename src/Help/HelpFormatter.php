<?php

declare(strict_types = 1);

namespace Phigue\Help;

use BackedEnum;
use Phigue\Shape\Field;
use Phigue\Shape\Shape;

final readonly class HelpFormatter
{
    public function format(Shape $shape): string
    {
        $options = [];
        $positionals = [];
        foreach ($shape->leaves() as $leaf) {
            if ($leaf->field->hidden) {
                continue;
            }

            if ($leaf->field->positional) {
                $positionals[] = $leaf->field;
                continue;
            }

            $options[] = $this->option($leaf->field);
        }

        return $this->usage($positionals) . "\n\nOptions:\n" . implode("\n", $options) . "\n";
    }

    /**
     * @param list<Field> $positionals
     */
    private function usage(array $positionals): string
    {
        $parts = ['Usage:', '[options]'];
        foreach ($positionals as $field) {
            $token = '<' . ( $field->cliLong ?? $field->name ) . '>';
            $parts[] = $field->hasDefault || $field->allowsNull ? '[' . $token . ']' : $token;
        }

        return implode(' ', $parts);
    }

    private function option(Field $field): string
    {
        $short = $field->cliShort !== null ? '-' . $field->cliShort . ', ' : '    ';
        $line = '  ' . $short . '--' . ( $field->cliLong ?? $field->name ) . $this->placeholder($field);

        if ($field->help !== null) {
            $line .= '  ' . $field->help;
        }

        $default = $this->renderDefault($field);
        if ($default !== null) {
            $line .= '  (default: ' . $default . ')';
        }

        return $line;
    }

    private function placeholder(Field $field): string
    {
        if ($field->type === 'bool' || $field->counted) {
            return '';
        }

        return ' <' . $field->type . '>';
    }

    private function renderDefault(Field $field): ?string
    {
        if (!$field->hasDefault) {
            return null;
        }

        if ($field->secret) {
            return '***';
        }

        $default = $field->default;

        return match (true) {
            $default === null => null,
            is_bool($default) => $default ? 'true' : 'false',
            $default instanceof BackedEnum => (string) $default->value,
            is_scalar($default) => (string) $default,
            default => null,
        };
    }
}
