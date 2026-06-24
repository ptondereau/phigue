<?php

declare(strict_types = 1);

namespace Phigue\Tests\Fixture;

enum LogLevel: string
{
    case Debug = 'debug';
    case Info = 'info';
    case Error = 'error';
}
