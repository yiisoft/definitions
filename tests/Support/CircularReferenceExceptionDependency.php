<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

use Yiisoft\Definitions\Exception\CircularReferenceException;

final class CircularReferenceExceptionDependency
{
    public function __construct()
    {
        throw new CircularReferenceException('Broken.');
    }
}
