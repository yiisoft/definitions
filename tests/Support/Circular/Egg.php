<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support\Circular;

class Egg
{
    public function __construct(Chicken $chicken)
    {
    }
}
