<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Objects;

final class OptionalInterfaceDependency
{
    public function __construct(EngineInterface $engine = null)
    {
    }
}
