<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

use RuntimeException;

final class RuntimeExceptionDependency
{
    public function __construct()
    {
        throw new RuntimeException('Broken.');
    }
}
