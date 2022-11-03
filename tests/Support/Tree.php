<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class Tree
{
    public function __construct(
        public string $name,
        public string|ColorInterface $color
    ) {
    }
}
