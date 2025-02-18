<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class MagicCall
{
    private array $record = [];

    public function __call($name, $arguments)
    {
        $arguments = array_map(
            static fn($argument) => get_debug_type($argument),
            $arguments,
        );

        $this->record[] = 'Call ' . $name . '(' . implode(', ', $arguments) . ')';
    }
}
