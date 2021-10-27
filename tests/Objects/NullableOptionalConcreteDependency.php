<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Objects;

final class NullableOptionalConcreteDependency
{
    public function __construct(?Car $car = null)
    {
    }
}
