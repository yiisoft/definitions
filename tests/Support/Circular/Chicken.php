<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support\Circular;

class Chicken
{
    public function __construct(Egg $egg)
    {
    }
}
