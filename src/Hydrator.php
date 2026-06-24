<?php

declare(strict_types = 1);

namespace Phigue;

use BackedEnum;
use Phigue\Exception\MappingError;
use Phigue\Exception\MissingRequired;
use Phigue\Exception\TypeMismatch;
use Phigue\Shape\Field;
use Phigue\Shape\Shape;

final readonly class Hydrator
{
    /**
     * @param array<array-key, mixed> $values
     */
    public function hydrate(Shape $shape, array $values): object
    {
        $args = [];
        $failures = [];

        foreach ($shape->fields as $field) {
            if (!array_key_exists($field->name, $values)) {
                if ($field->hasDefault) {
                    continue;
                }
                if ($field->allowsNull) {
                    $args[$field->name] = null;
                    continue;
                }
                $failures[] = new MissingRequired(sprintf('Missing required value for "%s".', $field->name));
                continue;
            }

            try {
                $args[$field->name] = $this->coerce($field, $values[$field->name]);
            } catch (MappingError $error) {
                $failures[] = $error;
            }
        }

        if ($failures !== []) {
            throw new MappingError(sprintf('Could not map configuration into %s:', $shape->target), $failures);
        }

        $target = $shape->target;

        return new $target(...$args);
    }

    private function coerce(Field $field, mixed $value): mixed
    {
        $type = $field->type;

        if ($value === null) {
            if ($field->allowsNull) {
                return null;
            }
            throw new TypeMismatch(sprintf('"%s" may not be null.', $field->name));
        }

        if ($field->isSubcommand()) {
            return $this->coerceSubcommand($field, $value);
        }

        if ($field->isScalar()) {
            return ScalarCoercer::coerce($field, $value);
        }

        if (is_subclass_of($type, BackedEnum::class)) {
            if (is_string($value) || is_int($value)) {
                $case = $type::tryFrom($value);
                if ($case !== null) {
                    return $case;
                }
            }
            throw new TypeMismatch(sprintf('"%s" is not a valid %s.', $field->name, $type));
        }

        if (class_exists($type)) {
            if ($value instanceof $type) {
                return $value;
            }
            if (is_array($value)) {
                return $this->hydrate(Shape::of($type), $value);
            }
            throw new TypeMismatch(sprintf('"%s" expects %s.', $field->name, $type));
        }

        return $value;
    }

    private function coerceSubcommand(Field $field, mixed $value): object
    {
        if (is_object($value)) {
            foreach ($field->subcommands as $class) {
                if ($value instanceof $class) {
                    return $value;
                }
            }
            throw new TypeMismatch(sprintf('"%s" is not one of its commands.', $field->name));
        }

        if (!is_array($value) || $value === []) {
            throw new TypeMismatch(sprintf('"%s" expects a command.', $field->name));
        }

        $name = array_key_first($value);
        $class = $field->subcommands[$name] ?? null;
        if ($class === null) {
            throw new TypeMismatch(sprintf('Unknown command "%s".', (string) $name));
        }

        $args = $value[$name];

        return $this->hydrate(Shape::of($class), PathTree::expand(is_array($args) ? $args : []));
    }
}
