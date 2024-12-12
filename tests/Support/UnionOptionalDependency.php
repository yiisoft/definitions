<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class UnionOptionalDependency
{
    public function __construct(
        private string|ColorInterface $value = 'test'
    ) {
    }

    public function getValue()
    {
        return $this->value;
    }
}
