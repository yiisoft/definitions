<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Helpers;

use Yiisoft\Definitions\Exception\InvalidConfigException;

/**
 * @internal
 */
final class ExceptionHelper
{
    /**
     * @throws InvalidConfigException
     */
    public static function throwInvalidArrayDefinitionKey(int|string $key): void
    {
        throw new InvalidConfigException(
            sprintf(
                'Invalid definition: invalid key in array definition. Only string keys are allowed, got %d.',
                $key,
            ),
        );
    }

    /**
     * @throws InvalidConfigException
     */
    public static function throwIncorrectArrayDefinitionConstructorArguments(mixed $value): void
    {
        throw new InvalidConfigException(
            sprintf(
                'Invalid definition: incorrect constructor arguments. Expected array, got %s.',
                get_debug_type($value)
            )
        );
    }

    /**
     * @throws InvalidConfigException
     */
    public static function throwIncorrectArrayDefinitionMethodArguments(string $key, mixed $value): void
    {
        throw new InvalidConfigException(
            sprintf(
                'Invalid definition: incorrect method "%s" arguments. Expected array, got "%s". ' .
                'Probably you should wrap them into square brackets.',
                $key,
                get_debug_type($value),
            )
        );
    }
}
