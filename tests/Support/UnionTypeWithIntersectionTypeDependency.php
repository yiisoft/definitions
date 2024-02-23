<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class UnionTypeWithIntersectionTypeDependency
{
    public function __construct(
        public Bike|(GearBox&stdClass)|Chair $dependency
    ) {
    }
}
