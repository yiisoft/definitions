<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests;

use Yiisoft\Definitions\Tests\Support\ColorInterface;
use Yiisoft\Definitions\Tests\Support\EngineInterface;

final class FunBike
{
    public function __construct(
        public string|ColorInterface $color,
        public string|EngineInterface $engine,
    ) {
    }
}
