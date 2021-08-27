<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Objects;

/**
 * A gear box.
 */
final class GearBox
{
    private int $maxGear;

    public function __construct(int $maxGear = 5)
    {
        $this->maxGear = $maxGear;
    }
}
