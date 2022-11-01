<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class UnionBuiltinDependency
{
    public function __construct(
        private string|int $value
    ) {
    }

    public function getValue()
    {
        return $this->value;
    }
}
