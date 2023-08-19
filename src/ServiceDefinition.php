<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Helpers\ArrayDefinitionHelper;

final class ServiceDefinition implements DefinitionInterface
{
    /**
     * @psalm-var array<string, mixed>
     */
    private array $calls = [];

    private function __construct(
        /**
         * @psalm-var class-string
         */
        private string $class,
        private array $constructor = [],
    ) {
    }

    /**
     * @psalm-param class-string $class
     */
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

    /**
     * @psalm-param array<string, array> $methods
     */
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

    /**
     * @psalm-param array<string, mixed> $properties
     */
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

    public function merge(self $other): self
    {
        $new = clone $this;
        $new->class = $other->class;
        $new->constructor = ArrayDefinitionHelper::mergeArguments($this->constructor, $other->constructor);

        $calls = $this->calls;
        foreach ($other->calls as $key => $item) {
            if (str_starts_with($key, '$')) {
                $calls[$key] = $item;
            } elseif (str_ends_with($key, '()')) {
                /** @psalm-suppress MixedArgument */
                $arguments = isset($calls[$key])
                    ? ArrayDefinitionHelper::mergeArguments($calls[$key], $item)
                    : $item;
                $calls[$key] = $arguments;
            }
        }
        $new->calls = $calls;

        return $new;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getConstructor(): array
    {
        return $this->constructor;
    }

    public function getCalls(): array
    {
        return $this->calls;
    }
}
