<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class OptionalInterfaceDependency
{
    public function __construct(EngineInterface $engine = null)
    {
    }
}
