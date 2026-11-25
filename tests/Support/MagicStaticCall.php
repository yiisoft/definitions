<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class MagicStaticCall
{
    public static function __callStatic($name, $arguments)
    {
        return 'Static call ' . $name . '(' . implode(', ', $arguments) . ')';
    }
}
