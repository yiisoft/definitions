<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class UnionBuiltinDependency
{
    private $value;

    public function __construct(string|int $value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}
