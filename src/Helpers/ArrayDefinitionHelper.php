<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Helpers;

use Yiisoft\Definitions\ArrayDefinition;

use Yiisoft\Definitions\Exception\InvalidConfigException;

use function is_array;

final class ArrayDefinitionHelper
{
    /**
     * @throws InvalidConfigException
     */
    public static function merge(array $configA, array $configB): array
    {
        /**
         * @var mixed $value
         */
        foreach ($configB as $key => $value) {
            if (!is_string($key)) {
                throw ExceptionHelper::invalidArrayDefinitionKey($key);
            }

            if (!isset($configA[$key])) {
                /** @var mixed */
                $configA[$key] = $value;
                continue;
            }

            if ($key === ArrayDefinition::CONSTRUCTOR) {
                if (!is_array($value)) {
                    throw ExceptionHelper::incorrectArrayDefinitionConstructorArguments($value);
                }
                if (!is_array($configA[$key])) {
                    throw ExceptionHelper::incorrectArrayDefinitionConstructorArguments($configA[$key]);
                }
                $configA[$key] = self::mergeArguments($configA[$key], $value);
                continue;
            }

            if (str_ends_with($key, '()')) {
                if (!is_array($value)) {
                    throw ExceptionHelper::incorrectArrayDefinitionMethodArguments($key, $value);
                }
                if (!is_array($configA[$key])) {
                    throw ExceptionHelper::incorrectArrayDefinitionMethodArguments($key, $configA[$key]);
                }
                /** @var mixed */
                $configA[$key] = self::mergeArguments($configA[$key], $value);
            }

            /** @var mixed */
            $configA[$key] = $value;
        }

        return $configA;
    }

    public static function mergeArguments(array $argumentsA, array $argumentsB): array
    {
        /** @var mixed $argument */
        foreach ($argumentsB as $name => $argument) {
            /** @var mixed */
            $argumentsA[$name] = $argument;
        }

        return $argumentsA;
    }
}
