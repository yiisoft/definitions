<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\DefinitionExtractor;
use Yiisoft\Definitions\Helpers\DefinitionResolver;

use function array_key_exists;
use function call_user_func_array;
use function count;
use function is_string;

/**
 * Builds an object by array config.
 *
 * @psalm-type MethodOrPropertyItem = array{0:string,1:string,2:mixed}
 * @psalm-type ArrayDefinitionConfig = array{class:class-string,'__construct()'?:array}&array<string, mixed>
 */
final class ArrayDefinition implements DefinitionInterface
{
    public const CLASS_NAME = 'class';
    public const CONSTRUCTOR = '__construct()';

    public const TYPE_PROPERTY = 'property';
    public const TYPE_METHOD = 'method';

    /**
     * @psalm-var class-string
     */
    private string $class;
    private array $constructorArguments;
    /**
      * Container used to resolve references.
      */
    private ?ContainerInterface $referenceContainer = null;

    /**
     * @psalm-var array<string, MethodOrPropertyItem>
     */
    private array $methodsAndProperties;

    /**
     * @psalm-param class-string $class
     * @psalm-param array<string, MethodOrPropertyItem> $methodsAndProperties
     */
    private function __construct(string $class, array $constructorArguments, array $methodsAndProperties)
    {
        $this->class = $class;
        $this->constructorArguments = $constructorArguments;
        $this->methodsAndProperties = $methodsAndProperties;
    }

    /**
     * @param ContainerInterface|null $referenceContainer Container to resolve references with.
     */
    public function setReferenceContainer(?ContainerInterface $referenceContainer): void
    {
        $this->referenceContainer = $referenceContainer;
    }

    /**
     * Create ArrayDefinition from array config.
     *
     * @psalm-param ArrayDefinitionConfig $config
     */
    public static function fromConfig(array $config): self
    {
        return new self(
            $config[self::CLASS_NAME],
            $config[self::CONSTRUCTOR] ?? [],
            self::getMethodsAndPropertiesFromConfig($config)
        );
    }

    /**
     * @psalm-param class-string $class
     * @psalm-param array<string, MethodOrPropertyItem> $methodsAndProperties
     */
    public static function fromPreparedData(string $class, array $constructorArguments = [], array $methodsAndProperties = []): self
    {
        return new self($class, $constructorArguments, $methodsAndProperties);
    }

    /**
     * @psalm-param array<string, mixed> $config
     *
     * @psalm-return array<string, MethodOrPropertyItem>
     */
    private static function getMethodsAndPropertiesFromConfig(array $config): array
    {
        $methodsAndProperties = [];

        /** @var mixed $value */
        foreach ($config as $key => $value) {
            if ($key === self::CONSTRUCTOR) {
                continue;
            }

            if (count($methodArray = explode('()', $key, 2)) === 2) {
                $methodsAndProperties[$key] = [self::TYPE_METHOD, $methodArray[0], $value];
            } elseif (count($propertyArray = explode('$', $key)) === 2) {
                $methodsAndProperties[$key] = [self::TYPE_PROPERTY, $propertyArray[1], $value];
            }
        }

        return $methodsAndProperties;
    }

    /**
     * @psalm-return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    public function getConstructorArguments(): array
    {
        return $this->constructorArguments;
    }

    /**
     * @psalm-return array<string, MethodOrPropertyItem>
     */
    public function getMethodsAndProperties(): array
    {
        return $this->methodsAndProperties;
    }

    public function resolve(ContainerInterface $container): object
    {
        $class = $this->getClass();
        $dependencies = DefinitionExtractor::fromClassName($class);
        $constructorArguments = $this->getConstructorArguments();

        $this->injectArguments($dependencies, $constructorArguments);

        /** @psalm-suppress MixedArgumentTypeCoercion */
        $resolved = DefinitionResolver::resolveArray($container, $this->referenceContainer, $dependencies);

        /** @psalm-suppress MixedMethodCall */
        $object = new $class(...array_values($resolved));

        foreach ($this->getMethodsAndProperties() as $item) {
            /** @var mixed $value */
            [$type, $name, $value] = $item;
            /** @var mixed */
            $value = DefinitionResolver::resolve($container, $this->referenceContainer, $value);
            if ($type === self::TYPE_METHOD) {
                /** @var mixed */
                $setter = call_user_func_array([$object, $name], $value);
                if ($setter instanceof $object) {
                    /** @var object */
                    $object = $setter;
                }
            } elseif ($type === self::TYPE_PROPERTY) {
                $object->$name = $value;
            }
        }

        return $object;
    }

    /**
     * @psalm-param array<string, ParameterDefinition> $dependencies
     * @psalm-param-out array<array-key, Yiisoft\Definitions\ParameterDefinition|mixed> $dependencies
     *
     * @throws InvalidConfigException
     */
    private function injectArguments(array &$dependencies, array $arguments): void
    {
        $isIntegerIndexed = $this->isIntegerIndexed($arguments);
        $dependencyIndex = 0;
        $variadicKey = null;

        foreach ($dependencies as $key => &$value) {
            if ($value->isVariadic()) {
                $variadicKey = $key;
            }
            $index = $isIntegerIndexed ? $dependencyIndex : $key;
            if (array_key_exists($index, $arguments)) {
                $value = DefinitionResolver::ensureResolvable($arguments[$index]);
            }
            $dependencyIndex++;
            if ($variadicKey !== null) {
                break;
            }
        }
        unset($value);
        if ($variadicKey !== null) {
            if (!$isIntegerIndexed && isset($arguments[$variadicKey])) {
                if (is_array($arguments[$variadicKey])) {
                    unset($dependencies[$variadicKey]);
                    $dependencies += $arguments[$variadicKey];
                    return;
                }

                throw new InvalidArgumentException(sprintf('Named argument for a variadic parameter should be an array, "%s" given.', gettype($arguments[$variadicKey])));
            }

            /** @var mixed $value */
            foreach (array_slice($arguments, $dependencyIndex) as $index => $value) {
                $dependencies[$index] = DefinitionResolver::ensureResolvable($value);
            }
        }
    }

    /**
     * @throws InvalidConfigException
     */
    private function isIntegerIndexed(array $arguments): bool
    {
        $hasStringIndex = false;
        $hasIntegerIndex = false;

        foreach ($arguments as $index => $_argument) {
            if (is_string($index)) {
                $hasStringIndex = true;
                if ($hasIntegerIndex) {
                    break;
                }
            } else {
                $hasIntegerIndex = true;
                if ($hasStringIndex) {
                    break;
                }
            }
        }
        if ($hasIntegerIndex && $hasStringIndex) {
            throw new InvalidConfigException(
                'Arguments indexed both by name and by position are not allowed in the same array.'
            );
        }

        return $hasIntegerIndex;
    }

    /**
     * Create a new definition that is merged from this definition and another definition.
     *
     * @param ArrayDefinition $other Definition to merge with.
     *
     * @return self New definition that is merged from this definition and another definition.
     */
    public function merge(self $other): self
    {
        $new = clone $this;
        $new->class = $other->class;
        $new->constructorArguments = $this->mergeArguments($this->constructorArguments, $other->constructorArguments);

        $methodsAndProperties = $this->methodsAndProperties;
        foreach ($other->methodsAndProperties as $key => $item) {
            if ($item[0] === self::TYPE_PROPERTY) {
                $methodsAndProperties[$key] = $item;
            } elseif ($item[0] === self::TYPE_METHOD) {
                /** @psalm-suppress MixedArgument, MixedAssignment */
                $arguments = isset($methodsAndProperties[$key])
                    ? $this->mergeArguments($methodsAndProperties[$key][2], $item[2])
                    : $item[2];
                $methodsAndProperties[$key] = [$item[0], $item[1], $arguments];
            }
        }
        $new->methodsAndProperties = $methodsAndProperties;

        return $new;
    }

    private function mergeArguments(array $selfArguments, array $otherArguments): array
    {
        /** @var mixed $argument */
        foreach ($otherArguments as $name => $argument) {
            /** @var mixed */
            $selfArguments[$name] = $argument;
        }

        return $selfArguments;
    }
}
