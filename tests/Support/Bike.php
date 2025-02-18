<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class Bike
{
    public function __construct(
        public string|ColorInterface $color,
        public EngineInterface $engine,
    ) {}
}
