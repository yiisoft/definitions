<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;

final class ServiceDefinition implements DefinitionInterface
{
    private array $constructorArguments = [];
    private string $class;
    private array $calls = [];

    public static function for(string $class): self
    {
        $definition = new self();
        $definition->class = $class;
        return $definition;
    }

    public function constructor(array $arguments): self
    {
        $this->constructorArguments = $arguments;
        return $this;
    }

    public function callMethod(string $method, array $arguments = []): self
    {
        $this->calls[$method] = $arguments;
        return $this;
    }

    public function callMethods(array $properties): self
    {
        foreach ($properties as $property => $value) {
            $this->callMethod($property, $value);
        }
        return $this;
    }

    public function setProperty(string $property, mixed $value): self
    {
        $this->calls[$property] = $value;
        return $this;
    }

    public function setProperties(array $properties): self
    {
        foreach ($properties as $property => $value) {
            $this->setProperty($property, $value);
        }
        return $this;
    }

    public function resolve(ContainerInterface $container): mixed
    {
        $config = [
            ArrayDefinition::CLASS_NAME => $this->class,
            ArrayDefinition::CONSTRUCTOR => $this->constructorArguments,
            ...$this->calls,
        ];
        return ArrayDefinition::fromConfig($config)->resolve($container);
    }

    public function merge(self $other)
    {
        // TBD
    }
}
