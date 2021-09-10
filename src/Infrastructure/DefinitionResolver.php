<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Infrastructure;

use Yiisoft\Definitions\CallableDefinition;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Contract\DependencyResolverInterface;
use Yiisoft\Definitions\Contract\ReferenceInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\ParameterDefinition;
use Yiisoft\Definitions\ValueDefinition;

use function is_array;

/**
 * @internal
 */
final class DefinitionResolver
{
    /**
     * Resolves dependencies by replacing them with the actual object instances.
     *
     * @param array $dependencies The dependencies.
     *
     * @psalm-param array<string,mixed> $dependencies
     *
     * @return array The resolved dependencies.
     *
     * @psalm-return array<string,mixed>
     */
    public static function resolveArray(DependencyResolverInterface $dependencyResolver, array $dependencies): array
    {
        $result = [];
        /** @var mixed $definition */
        foreach ($dependencies as $key => $definition) {
            if (
                $definition instanceof ParameterDefinition &&
                (
                    $definition->isVariadic() ||
                    ($definition->isOptional() && !$definition->hasValue())
                )
            ) {
                continue;
            }

            /** @var mixed */
            $result[$key] = self::resolve($dependencyResolver, $definition);
        }

        return $result;
    }

    /**
     * This function resolves a definition recursively, checking for loops.
     *
     * @param mixed $definition
     *
     * @return mixed
     */
    public static function resolve(DependencyResolverInterface $dependencyResolver, $definition)
    {
        if ($definition instanceof DefinitionInterface) {
            /** @var mixed $definition */
            $definition = $definition->resolve($dependencyResolver);
        } elseif (is_array($definition)) {
            /** @psalm-var array<string,mixed> $definition */
            return self::resolveArray($dependencyResolver, $definition);
        }

        return $definition;
    }

    /**
     * @param mixed $value
     *
     * @throws InvalidConfigException
     *
     * @return array|ReferenceInterface|ValueDefinition
     */
    public static function ensureResolvable($value)
    {
        if ($value instanceof ReferenceInterface || is_array($value)) {
            return $value;
        }

        if ($value instanceof DefinitionInterface) {
            throw new InvalidConfigException(
                'Only references are allowed in constructor arguments, a definition object was provided: ' .
                var_export($value, true)
            );
        }

        return new ValueDefinition($value);
    }
}
