<?php

declare(strict_types = 1);

namespace Phigue\Tests\Integration;

use Phigue\Tests\Fixture\Console\ServeCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SymfonyConsoleTest extends TestCase
{
    #[Test]
    public function env_fills_the_config_when_no_cli_option_is_passed(): void
    {
        $tester = new CommandTester(new ServeCommand(['APP_PORT' => '9000']));

        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('127.0.0.1:9000', $tester->getDisplay());
    }

    #[Test]
    public function a_cli_option_overrides_env(): void
    {
        $tester = new CommandTester(new ServeCommand(['APP_PORT' => '9000']));

        $tester->execute(['--port' => '1234']);

        self::assertStringContainsString(':1234', $tester->getDisplay());
    }

    #[Test]
    public function defaults_apply_when_nothing_is_provided(): void
    {
        $tester = new CommandTester(new ServeCommand());

        $tester->execute([]);

        self::assertStringContainsString('127.0.0.1:8080', $tester->getDisplay());
    }
}
