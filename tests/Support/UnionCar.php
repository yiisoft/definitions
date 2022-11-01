<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class UnionCar
{
    public function __construct(
        private NonExistingEngine|EngineMarkOne|EngineMarkTwo $engine
    ) {
    }

    public function getEngine(): NonExistingEngine|EngineMarkOne|EngineMarkTwo
    {
        return $this->engine;
    }

    public function getEngineName(): string
    {
        return $this->engine->getName();
    }
}
