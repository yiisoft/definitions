<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;

final class ServiceDefinition implements DefinitionInterface
{
    private array $calls = [];

    private function __construct(
        private string $class,
        private array $constructor = [],
    ) {
    }

    public static function for(string $class, array $constructor = []): self
    {
        return new self($class, $constructor);
    }

    public function constructor(array $arguments): self
    {
        $this->constructor = $arguments;
        return $this;
    }

    public function call(string $method, array $arguments = []): self
    {
        $this->calls[$method . '()'] = $arguments;
        return $this;
    }

    public function calls(array $methods): self
    {
        foreach ($methods as $method => $arguments) {
            $this->call($method, $arguments);
        }
        return $this;
    }

    public function set(string $property, mixed $value): self
    {
        $this->calls['$' . $property] = $value;
        return $this;
    }

    public function sets(array $properties): self
    {
        foreach ($properties as $property => $value) {
            $this->set($property, $value);
        }
        return $this;
    }

    public function resolve(ContainerInterface $container): mixed
    {
        $config = array_merge($this->calls, [
            ArrayDefinition::CLASS_NAME => $this->class,
            ArrayDefinition::CONSTRUCTOR => $this->constructor,
        ]);
        return ArrayDefinition::fromConfig($config)->resolve($container);
    }

    public function merge(self $other)
    {
        // TBD
    }
}
