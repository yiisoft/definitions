<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class NullableOptionalConcreteDependency
{
    public function __construct(?Car $car = null) {}
}
