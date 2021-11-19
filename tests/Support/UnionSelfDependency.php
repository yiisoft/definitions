<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class UnionSelfDependency
{
    public function __construct(self|ColorInterface $a)
    {
    }
}
