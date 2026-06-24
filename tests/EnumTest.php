<?php

declare(strict_types = 1);

namespace Phigue\Tests;

use Phigue\Exception\MappingError;
use Phigue\Layered;
use Phigue\Tests\Fixture\LoggingConfig;
use Phigue\Tests\Fixture\LogLevel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnumTest extends TestCase
{
    #[Test]
    public function it_coerces_a_backed_enum_from_its_scalar_value(): void
    {
        $config = Layered::for(LoggingConfig::class)->values(['level' => 'error'])->build();

        self::assertSame(LogLevel::Error, $config->level);
    }

    #[Test]
    public function it_rejects_an_invalid_enum_value(): void
    {
        $this->expectException(MappingError::class);

        Layered::for(LoggingConfig::class)->values(['level' => 'verbose'])->build();
    }
}
