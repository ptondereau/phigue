<?php

declare(strict_types = 1);

namespace Phigue\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Hidden
{
}
