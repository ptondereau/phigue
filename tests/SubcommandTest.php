<?php

declare(strict_types = 1);

namespace Phigue\Tests;

use Phigue\Exception\MappingError;
use Phigue\Layered;
use Phigue\Tests\Fixture\Cli;
use Phigue\Tests\Fixture\MigrateCommand;
use Phigue\Tests\Fixture\ServeCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SubcommandTest extends TestCase
{
    #[Test]
    public function it_resolves_a_subcommand_from_a_discriminated_array(): void
    {
        $cli = Layered::for(Cli::class)->values(['command' => ['serve' => ['workers' => 8]]])->build();

        self::assertInstanceOf(ServeCommand::class, $cli->command);
        self::assertSame(8, $cli->command->workers);
    }

    #[Test]
    public function it_selects_a_command_by_name_and_parses_its_options_from_argv(): void
    {
        $cli = Layered::for(Cli::class)->args(['serve', '--workers', '8'])->build();

        self::assertInstanceOf(ServeCommand::class, $cli->command);
        self::assertSame(8, $cli->command->workers);
    }

    #[Test]
    public function it_parses_a_command_positional_from_argv(): void
    {
        $cli = Layered::for(Cli::class)->args(['migrate', 'users'])->build();

        self::assertInstanceOf(MigrateCommand::class, $cli->command);
        self::assertSame('users', $cli->command->target);
    }

    #[Test]
    public function it_parses_global_options_before_the_command(): void
    {
        $cli = Layered::for(Cli::class)->args(['-vv', 'serve', '--workers', '3'])->build();

        self::assertSame(2, $cli->verbosity);
        self::assertInstanceOf(ServeCommand::class, $cli->command);
        self::assertSame(3, $cli->command->workers);
    }

    #[Test]
    public function it_rejects_an_unknown_command(): void
    {
        $this->expectException(MappingError::class);

        Layered::for(Cli::class)->args(['frobnicate'])->build();
    }
}
