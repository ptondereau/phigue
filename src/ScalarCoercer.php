<?php

declare(strict_types = 1);

namespace Phigue;

use Phigue\Exception\TypeMismatch;
use Phigue\Shape\Field;

final readonly class ScalarCoercer
{
    public static function coerce(Field $field, mixed $value): int|float|string|bool
    {
        return match ($field->type) {
            'string' => is_scalar($value)
                ? (string) $value
                : throw new TypeMismatch(sprintf('"%s" expects string.', $field->name)),
            'int' => self::toInt($field, $value),
            'float' => self::toFloat($field, $value),
            'bool' => self::toBool($field, $value),
            default => throw new TypeMismatch(sprintf('"%s" has unsupported type %s.', $field->name, $field->type)),
        };
    }

    private static function toInt(Field $field, mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
            return (int) $value;
        }
        throw new TypeMismatch(sprintf('"%s" expects int, got "%s".', $field->name, self::display($value)));
    }

    private static function toFloat(Field $field, mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (is_string($value) && is_numeric(trim($value))) {
            return (float) $value;
        }
        throw new TypeMismatch(sprintf('"%s" expects float, got "%s".', $field->name, self::display($value)));
    }

    private static function toBool(Field $field, mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = is_string($value) ? strtolower(trim($value)) : $value;

        return match ($normalized) {
            true, 1, '1', 'true', 'yes', 'on' => true,
            false, 0, '0', 'false', 'no', 'off', '' => false,
            default => throw new TypeMismatch(sprintf(
                '"%s" expects bool, got "%s".',
                $field->name,
                self::display($value),
            )),
        };
    }

    private static function display(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : get_debug_type($value);
    }
}
