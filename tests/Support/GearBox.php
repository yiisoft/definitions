<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

/**
 * A gear box.
 */
final class GearBox
{
    public function __construct(
        private int $maxGear = 5,
    ) {}
}
