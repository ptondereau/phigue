<?php

declare(strict_types = 1);

namespace PHacet\Tests\Fixture;

use RuntimeException;

final class BackendDown extends RuntimeException implements \Psr\Cache\CacheException, \Psr\SimpleCache\CacheException
{
}
