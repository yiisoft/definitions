<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Infrastructure\ArrayDefinitionBuilder;

use function count;

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
        return ArrayDefinitionBuilder::getInstance()->build($container, $this->referenceContainer, $this);
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
