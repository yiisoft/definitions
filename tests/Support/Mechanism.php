<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class Mechanism
{
    public function __construct(
        public EngineInterface $engine,
        public string|ColorInterface $color,
    ) {
    }
}
