<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class NullableConcreteDependency
{
    public function __construct(?Car $car)
    {
    }
}
