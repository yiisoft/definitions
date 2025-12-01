<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class Recorder
{
    private static array $staticRecord = [];
    private array $record = [];

    public function __call($name, $arguments)
    {
        $arguments = array_map(
            get_debug_type(...),
            $arguments,
        );

        $this->record[] = 'Call ' . $name . '(' . implode(', ', $arguments) . ')';
    }

    public static function __callStatic($name, $arguments)
    {
        $arguments = array_map(
            get_debug_type(...),
            $arguments,
        );

        self::$staticRecord[] = 'Call ' . $name . '(' . implode(', ', $arguments) . ')';
    }

    public function __isset($name)
    {
        return true;
    }

    public function __set($name, $value)
    {
        $this->record[] = "Set $$name to " . get_debug_type($value);
    }

    public function __get($name)
    {
        return null;
    }

    public function getEvents(): array
    {
        return $this->record;
    }
}
