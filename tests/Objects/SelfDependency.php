<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Objects;

final class SelfDependency
{
    public function __construct(self $a)
    {
    }
}
