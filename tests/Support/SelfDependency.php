<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class SelfDependency
{
    public function __construct(self $a) {}
}
