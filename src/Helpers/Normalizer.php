<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Helpers;

use WeakMap;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\CallableDefinition;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Contract\ReferenceInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\ValueDefinition;

use function array_key_exists;
use function count;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;

/**
 * Normalizer definition from configuration to an instance of {@see DefinitionInterface}.
 *
 * @psalm-import-type ArrayDefinitionConfig from ArrayDefinition
 */
final class Normalizer
{
    /**
     * @var array<class-string, ArrayDefinition>
     */
    private static array $classDefinitions = [];

    /**
     * @var array<string, Reference>
     */
    private static array $references = [];

    /**
     * @var array<string, CallableDefinition>
     */
    private static array $callables = [];

    /**
     * @var WeakMap<object, ValueDefinition>|null
     */
    private static ?WeakMap $values = null;

    /**
     * Clear internal normalization caches.
     *
     * This is useful for long-running processes that normalize dynamically generated service IDs.
     */
    public static function clearCache(): void
    {
        self::$classDefinitions = [];
        self::$references = [];
        self::$callables = [];
        self::$values = null;
    }

    /**
     * Normalize definition to an instance of {@see DefinitionInterface}.
     * Definition may be defined multiple ways:
     *  - class name,
     *  - string as reference to another class or alias,
     *  - instance of {@see ReferenceInterface},
     *  - callable,
     *  - array,
     *  - ready object.
     *
     * @param mixed $definition The definition for normalization.
     * @param string|null $class The class name of the object to be defined (optional). It is used in two cases.
     *  - The definition is a string, and class name equals to definition. Returned `ArrayDefinition` with defined
     *    class.
     *  - The definition is an array without class name. Class name will be added to array and `ArrayDefinition`
     *    will be returned.
     *
     * @throws InvalidConfigException If configuration is not valid.
     *
     * @return DefinitionInterface Normalized definition as an object.
     */
    public static function normalize(mixed $definition, ?string $class = null): DefinitionInterface
    {
        // Reference
        if ($definition instanceof ReferenceInterface) {
            return $definition;
        }

        if (is_string($definition)) {
            // Current class
            if ($class === $definition) {
                /** @psalm-var class-string $definition */
                return self::$classDefinitions[$definition] ??= ArrayDefinition::fromPreparedData($definition);
            }

            if ($class === null && isset(self::$classDefinitions[$definition])) {
                /** @psalm-var class-string $definition */
                return self::$classDefinitions[$definition];
            }

            if ($class === null && isset(self::$references[$definition])) {
                return self::$references[$definition];
            }

            if ($class === null && $definition !== '') {
                if (class_exists($definition)) {
                    /** @psalm-var class-string $definition */
                    return self::$classDefinitions[$definition] ??= ArrayDefinition::fromPreparedData($definition);
                }
            }

            // Reference to another class or alias
            return self::$references[$definition] ??= Reference::to($definition);
        }

        // Callable array definition
        if (
            is_array($definition)
            && isset($definition[0], $definition[1])
            && count($definition) === 2
            && is_string($definition[1])
            && (is_string($definition[0]) || is_object($definition[0]))
        ) {
            if (is_string($definition[0])) {
                return self::$callables[$definition[0] . "\0" . $definition[1]]
                    ??= new CallableDefinition($definition);
            }

            return new CallableDefinition($definition);
        }

        // Array definition
        if (is_array($definition)) {
            if (
                isset($definition[ArrayDefinition::CLASS_NAME])
                || $class !== null
                || array_key_exists(ArrayDefinition::CLASS_NAME, $definition)
            ) {
                $config = $definition;
                if (!isset($config[ArrayDefinition::CLASS_NAME]) && !array_key_exists(ArrayDefinition::CLASS_NAME, $config)) {
                    $config[ArrayDefinition::CLASS_NAME] = $class;
                }
                /** @psalm-var ArrayDefinitionConfig $config */
                return ArrayDefinition::fromConfig($config);
            }
        }

        // Callable definition
        if (is_callable($definition, true)) {
            return new CallableDefinition($definition);
        }

        if (is_array($definition)) {
            throw new InvalidConfigException(
                'Array definition should contain the key "class": ' . var_export($definition, true),
            );
        }

        // Ready object
        if (is_object($definition) && !($definition instanceof DefinitionInterface)) {
            if (self::$values === null) {
                /** @var WeakMap<object, ValueDefinition> $values */
                $values = new WeakMap();
                self::$values = $values;
            } else {
                $values = self::$values;
            }

            $value = $values[$definition] ?? null;
            if ($value instanceof ValueDefinition) {
                return $value;
            }

            $value = new ValueDefinition($definition);
            $values[$definition] = $value;
            return $value;
        }

        throw new InvalidConfigException('Invalid definition: ' . var_export($definition, true));
    }
}
