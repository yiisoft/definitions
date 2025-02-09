<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Benchmark;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\CallableDefinition;
use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\ValueDefinition;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\DefinitionStorage;

class TestClass
{
    private string $property;

    public function __construct(string $param1, string $param2)
    {
        $this->property = $param1 . $param2;
    }
}

#[BeforeMethods('setUp')]
final class DefinitionBench
{
    private DefinitionStorage $storage;
    private ContainerInterface $container;

    public function setUp(): void
    {
        $this->storage = new DefinitionStorage();
        // Create a simple container implementation for testing
        $this->container = new class () implements ContainerInterface {
            private array $entries = [];

            public function get(string $id): mixed
            {
                return $this->entries[$id] ?? null;
            }

            public function has(string $id): bool
            {
                return isset($this->entries[$id]);
            }

            public function set(string $id, mixed $value): void
            {
                $this->entries[$id] = $value;
            }
        };
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchArrayDefinitionResolve(): void
    {
        $definition = ArrayDefinition::fromConfig([
            'class' => TestClass::class,
            'property' => 'value',
            '__construct()' => ['param1', 'param2'],
        ]);
        $definition->resolve($this->container);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchCallableDefinitionResolve(): void
    {
        $definition = new CallableDefinition(static fn () => new \stdClass());
        $definition->resolve($this->container);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchValueDefinitionResolve(): void
    {
        $definition = new ValueDefinition('test value');
        $definition->resolve($this->container);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchReferenceResolve(): void
    {
        $reference = Reference::to('dependency');
        $this->container->set('dependency', 'test');
        $reference->resolve($this->container);
    }

    #[Revs(1000)]
    #[Iterations(10)]
    public function benchDynamicReferenceResolve(): void
    {
        $reference = DynamicReference::to(static fn () => 'test');
        $reference->resolve($this->container);
    }
}
