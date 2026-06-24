<?php

declare(strict_types = 1);

namespace Phigue\Tests;

use Phigue\Layered;
use Phigue\Tests\Fixture\CliConfig;
use Phigue\Tests\Fixture\ServerConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ArgvTest extends TestCase
{
    #[Test]
    public function it_parses_long_options_with_separate_and_inline_values(): void
    {
        $config = Layered::for(ServerConfig::class)->args(['--host', 'cli-host', '--port=7000'])->build();

        self::assertSame('cli-host', $config->host);
        self::assertSame(7000, $config->port);
    }

    #[Test]
    public function it_parses_short_options(): void
    {
        $config = Layered::for(ServerConfig::class)->args(['-H', 'short-host', '-p', '5'])->build();

        self::assertSame('short-host', $config->host);
        self::assertSame(5, $config->port);
    }

    #[Test]
    public function a_bare_boolean_flag_is_true(): void
    {
        $config = Layered::for(ServerConfig::class)->args(['--tls'])->build();

        self::assertTrue($config->tls);
    }

    #[Test]
    public function it_counts_a_compact_repeated_short_flag(): void
    {
        $config = Layered::for(CliConfig::class)->args(['-vvv'])->build();

        self::assertSame(3, $config->verbosity);
    }

    #[Test]
    public function it_counts_repeated_separate_short_flags(): void
    {
        $config = Layered::for(CliConfig::class)->args(['-v', '-v'])->build();

        self::assertSame(2, $config->verbosity);
    }

    #[Test]
    public function it_assigns_bare_arguments_to_positional_fields(): void
    {
        $config = Layered::for(CliConfig::class)->args(['-vv', 'input.txt'])->build();

        self::assertSame(2, $config->verbosity);
        self::assertSame('input.txt', $config->input);
    }
}
