<?php

declare(strict_types = 1);

namespace Phigue\Shape;

use Phigue\Attribute\Counted;
use Phigue\Attribute\Env;
use Phigue\Attribute\Flatten;
use Phigue\Attribute\Help;
use Phigue\Attribute\Hidden;
use Phigue\Attribute\Named;
use Phigue\Attribute\Positional;
use Phigue\Attribute\Secret;
use Phigue\Attribute\Subcommand;
use Phigue\Exception\MappingError;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

final readonly class Shape
{
    /**
     * @param class-string $target
     * @param list<Field> $fields
     */
    private function __construct(
        public string $target,
        public array $fields,
    ) {
    }

    /**
     * @param class-string $target
     */
    public static function of(string $target): self
    {
        $reflection = new ReflectionClass($target);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new self($target, []);
        }

        $fields = [];
        foreach ($constructor->getParameters() as $parameter) {
            $fields[] = self::fieldFor($parameter);
        }

        return new self($target, $fields);
    }

    public function field(string $name): ?Field
    {
        foreach ($this->fields as $field) {
            if ($field->name === $name) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return list<Leaf>
     */
    public function leaves(): array
    {
        return self::leavesOf($this, []);
    }

    /**
     * @param list<string> $prefix
     * @return list<Leaf>
     */
    private static function leavesOf(self $shape, array $prefix): array
    {
        $leaves = [];
        foreach ($shape->fields as $field) {
            if ($field->flatten && class_exists($field->type)) {
                $leaves = [
                    ...$leaves,
                    ...self::leavesOf(self::of($field->type), [...$prefix, $field->name]),
                ];
                continue;
            }

            $leaves[] = new Leaf([...$prefix, $field->name], $field);
        }

        return $leaves;
    }

    private static function fieldFor(ReflectionParameter $parameter): Field
    {
        if (self::attribute($parameter, Subcommand::class) !== null) {
            return self::subcommandField($parameter);
        }

        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType) {
            throw new MappingError(sprintf('Parameter "$%s" must have a single named type.', $parameter->getName()));
        }

        $named = self::attribute($parameter, Named::class);
        $positional = self::attribute($parameter, Positional::class);
        $env = self::attribute($parameter, Env::class);
        $help = self::attribute($parameter, Help::class);

        $name = $parameter->getName();

        return new Field(
            name: $name,
            type: $type->getName(),
            allowsNull: $type->allowsNull(),
            hasDefault: $parameter->isDefaultValueAvailable(),
            default: $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
            cliLong: $named !== null ? $named->long ?? Naming::kebab($name) : Naming::kebab($name),
            cliShort: $named?->short,
            positional: $positional !== null,
            positionalIndex: $positional?->index,
            counted: self::attribute($parameter, Counted::class) !== null,
            envName: $env !== null ? $env->name ?? Naming::screamingSnake($name) : Naming::screamingSnake($name),
            flatten: self::attribute($parameter, Flatten::class) !== null,
            hidden: self::attribute($parameter, Hidden::class) !== null,
            secret: self::attribute($parameter, Secret::class) !== null,
            help: $help?->text,
        );
    }

    private static function subcommandField(ReflectionParameter $parameter): Field
    {
        $type = $parameter->getType();
        $members = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];

        $subcommands = [];
        foreach ($members as $member) {
            if (!$member instanceof ReflectionNamedType || $member->isBuiltin()) {
                continue;
            }

            $class = $member->getName();
            $subcommands[Naming::command($class)] = $class;
        }

        if ($subcommands === []) {
            throw new MappingError(sprintf(
                'Subcommand "$%s" must be a union of command classes.',
                $parameter->getName(),
            ));
        }

        return new Field(
            name: $parameter->getName(),
            type: '',
            allowsNull: $parameter->allowsNull(),
            hasDefault: $parameter->isDefaultValueAvailable(),
            default: $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
            subcommands: $subcommands,
        );
    }

    /**
     * @template TAttr of object
     * @param class-string<TAttr> $attribute
     * @return TAttr|null
     */
    private static function attribute(ReflectionParameter $parameter, string $attribute): ?object
    {
        $attributes = $parameter->getAttributes($attribute);

        return $attributes === [] ? null : $attributes[0]->newInstance();
    }
}
