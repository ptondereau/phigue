<?php

declare(strict_types = 1);

namespace Phigue\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::TARGET_CLASS)]
final readonly class Help
{
    public function __construct(
        public string $text,
    ) {
    }
}
