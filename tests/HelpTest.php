<?php

declare(strict_types = 1);

namespace Phigue\Tests;

use Phigue\Layered;
use Phigue\Tests\Fixture\DocumentedConfig;
use Phigue\Tests\Fixture\ServerConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HelpTest extends TestCase
{
    #[Test]
    public function it_lists_an_option_with_flags_type_and_default(): void
    {
        $help = Layered::for(ServerConfig::class)->help();

        self::assertStringContainsString('--host', $help);
        self::assertStringContainsString('-H', $help);
        self::assertStringContainsString('<string>', $help);
        self::assertStringContainsString('127.0.0.1', $help);
    }

    #[Test]
    public function it_shows_help_text_for_an_option(): void
    {
        $help = Layered::for(DocumentedConfig::class)->help();

        self::assertStringContainsString('Address to bind to', $help);
    }

    #[Test]
    public function it_excludes_hidden_fields(): void
    {
        $help = Layered::for(DocumentedConfig::class)->help();

        self::assertStringNotContainsString('--debug', $help);
    }

    #[Test]
    public function it_shows_positional_fields_in_a_usage_line(): void
    {
        $help = Layered::for(DocumentedConfig::class)->help();

        self::assertStringContainsString('Usage:', $help);
        self::assertStringContainsString('<config-path>', $help);
    }

    #[Test]
    public function it_redacts_a_secret_default(): void
    {
        $help = Layered::for(DocumentedConfig::class)->help();

        self::assertStringContainsString('--api-key', $help);
        self::assertStringNotContainsString('sk-default', $help);
    }
}
