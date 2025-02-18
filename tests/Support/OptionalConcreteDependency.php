<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class OptionalConcreteDependency
{
    public function __construct(Car $car = null) {}
}
