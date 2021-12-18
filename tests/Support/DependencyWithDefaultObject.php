<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class DependencyWithDefaultObject
{
    private Chair $chair;

    public function __construct(Chair $chair = new RedChair())
    {
        $this->chair = $chair;
    }

    public function getChair()
    {
        return $this->chair;
    }
}
