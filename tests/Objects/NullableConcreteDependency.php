<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Objects;

final class NullableConcreteDependency
{
    public function __construct(?Car $car)
    {
    }
}
