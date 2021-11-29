<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Helpers;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;
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
     * @param ContainerInterface $container Container to get dependencies from.
     * @param ContainerInterface|null $referenceContainer Container to get references from.
     * @psalm-param array<string,mixed> $definitions Definitions to resolve.
     *
     * @return array The resolved dependencies.
     */
    public static function resolveArray(ContainerInterface $container, ?ContainerInterface $referenceContainer, array $definitions): array
    {
        $result = [];
        /** @var mixed $definition */
        foreach ($definitions as $key => $definition) {
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
            $result[$key] = self::resolve($container, $referenceContainer, $definition);
        }

        return $result;
    }

    /**
     * This function resolves a definition recursively, checking for loops.
     *
     * @param mixed $definition Definition to resolve.
     *
     * @return mixed
     */
    public static function resolve(ContainerInterface $container, ?ContainerInterface $referenceContainer, $definition)
    {
        if ($definition instanceof DefinitionInterface) {
            $container = $referenceContainer !== null && $definition instanceof ReferenceInterface
                ? $referenceContainer
                : $container;
            /** @var mixed $definition */
            $definition = $definition->resolve($container);
        } elseif (is_array($definition)) {
            /** @psalm-var array<string,mixed> $definition */
            return self::resolveArray($container, $referenceContainer, $definition);
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
