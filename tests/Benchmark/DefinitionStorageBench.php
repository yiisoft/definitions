<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Benchmark;

use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use Yiisoft\Definitions\DefinitionStorage;
use Yiisoft\Definitions\Tests\Support\Car;
use Yiisoft\Definitions\Tests\Support\ColorInterface;
use Yiisoft\Definitions\Tests\Support\ColorPink;
use Yiisoft\Definitions\Tests\Support\EngineInterface;
use Yiisoft\Definitions\Tests\Support\EngineMarkOne;
use Yiisoft\Test\Support\Container\SimpleContainer;

/**
 * @Iterations(5)
 * @Revs(1000)
 * @BeforeMethods({"before"})
 */
final class DefinitionStorageBench
{
    private const SERVICE_COUNT = 200;

    /**
     * @var int[]
     */
    private array $indexes = [];

    /**
     * @var array<string, string>
     */
    private array $definitions = [];

    private DefinitionStorage $explicitStorage;
    private DefinitionStorage $resolvableStorage;
    private DefinitionStorage $unresolvableStorage;
    private DefinitionStorage $delegateStorage;
    private DefinitionStorage $strictStorage;

    public function before(): void
    {
        $this->indexes = [];
        $this->definitions = [
            EngineInterface::class => EngineMarkOne::class,
            ColorInterface::class => ColorPink::class,
        ];

        for ($i = 0; $i < self::SERVICE_COUNT; $i++) {
            $this->indexes[] = $i;
            $this->definitions["service$i"] = Car::class;
        }

        $this->explicitStorage = new DefinitionStorage($this->definitions);
        $this->resolvableStorage = new DefinitionStorage([
            EngineInterface::class => EngineMarkOne::class,
        ]);
        $this->unresolvableStorage = new DefinitionStorage();
        $this->delegateStorage = new DefinitionStorage();
        $this->delegateStorage->setDelegateContainer(new SimpleContainer([
            EngineInterface::class => new EngineMarkOne(),
        ]));
        $this->strictStorage = new DefinitionStorage([], true);

        $this->resolvableStorage->has(Car::class);
        $this->delegateStorage->has(Car::class);
    }

    /**
     * Measures construction with a typical services map.
     *
     * @Groups({"construct", "storage"})
     */
    public function benchConstruct(): void
    {
        new DefinitionStorage($this->definitions);
    }

    /**
     * Measures positive lookups of explicitly configured service IDs.
     *
     * @Groups({"lookup", "storage", "cold"})
     */
    public function benchSequentialExplicitLookupsColdStorage(): void
    {
        $storage = new DefinitionStorage($this->definitions);

        foreach ($this->indexes as $index) {
            $storage->has("service$index");
        }
    }

    /**
     * Measures positive lookups against a reused definitions storage.
     *
     * @Groups({"lookup", "storage", "warm", "typical"})
     */
    public function benchSequentialExplicitLookupsWarmStorage(): void
    {
        foreach ($this->indexes as $index) {
            $this->explicitStorage->has("service$index");
        }
    }

    /**
     * Measures positive autowiring checks for an object graph.
     *
     * @Groups({"lookup", "storage", "autowire", "typical"})
     * @Revs(100)
     */
    public function benchResolvableObjectGraphColdStorage(): void
    {
        $storage = new DefinitionStorage([
            EngineInterface::class => EngineMarkOne::class,
        ]);

        $storage->has(Car::class);
    }

    /**
     * Measures positive autowiring checks for an object graph against reused storage.
     *
     * @Groups({"lookup", "storage", "autowire", "warm", "typical"})
     * @Revs(100)
     */
    public function benchResolvableObjectGraphWarmStorage(): void
    {
        $this->resolvableStorage->has(Car::class);
    }

    /**
     * Measures negative autowiring checks for a missing dependency.
     *
     * @Groups({"lookup", "storage", "autowire", "typical"})
     * @Revs(100)
     */
    public function benchUnresolvableObjectGraphColdStorage(): void
    {
        $storage = new DefinitionStorage();

        $storage->has(Car::class);
    }

    /**
     * Measures negative autowiring checks for a missing dependency against reused storage.
     *
     * @Groups({"lookup", "storage", "autowire", "warm", "typical"})
     * @Revs(100)
     */
    public function benchUnresolvableObjectGraphWarmStorage(): void
    {
        $this->unresolvableStorage->has(Car::class);
    }

    /**
     * Measures fallback through a delegate container when a dependency is not explicitly defined.
     *
     * @Groups({"lookup", "storage", "delegate", "typical"})
     * @Revs(100)
     */
    public function benchResolvableObjectGraphWithDelegateColdStorage(): void
    {
        $storage = new DefinitionStorage();
        $storage->setDelegateContainer(new SimpleContainer([
            EngineInterface::class => new EngineMarkOne(),
        ]));

        $storage->has(Car::class);
    }

    /**
     * Measures fallback through a delegate container against reused storage.
     *
     * @Groups({"lookup", "storage", "delegate", "warm", "typical"})
     * @Revs(100)
     */
    public function benchResolvableObjectGraphWithDelegateWarmStorage(): void
    {
        $this->delegateStorage->has(Car::class);
    }

    /**
     * Measures strict mode misses where class names are not implicitly resolvable.
     *
     * @Groups({"lookup", "storage", "strict"})
     */
    public function benchStrictModeClassMisses(): void
    {
        foreach ($this->indexes as $_index) {
            $this->strictStorage->has(EngineMarkOne::class);
        }
    }
}
