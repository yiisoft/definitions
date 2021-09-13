<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Objects;

final class UnionSelfDependency
{
    public function __construct(self|ColorInterface $a)
    {
    }
}
