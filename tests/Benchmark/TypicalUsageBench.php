<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Benchmark;

use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\CallableDefinition;
use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\Tests\Support\Car;
use Yiisoft\Definitions\Tests\Support\CarFactory;
use Yiisoft\Definitions\Tests\Support\ColorInterface;
use Yiisoft\Definitions\Tests\Support\ColorPink;
use Yiisoft\Definitions\Tests\Support\EngineInterface;
use Yiisoft\Definitions\Tests\Support\EngineMarkOne;
use Yiisoft\Definitions\Tests\Support\Phone;
use Yiisoft\Test\Support\Container\SimpleContainer;

/**
 * @Iterations(5)
 * @Revs(1000)
 * @BeforeMethods({"before"})
 */
final class TypicalUsageBench
{
    private SimpleContainer $container;
    private ArrayDefinition $objectGraphDefinition;
    private ArrayDefinition $methodsAndPropertiesDefinition;
    private CallableDefinition $staticFactoryDefinition;
    private CallableDefinition $serviceFactoryDefinition;
    private Reference $reference;

    public function before(): void
    {
        $this->container = new SimpleContainer([
            EngineInterface::class => new EngineMarkOne(),
            ColorInterface::class => new ColorPink(),
            CarFactory::class => new CarFactory(),
        ]);

        $this->objectGraphDefinition = ArrayDefinition::fromConfig([
            'class' => Car::class,
            '__construct()' => [Reference::to(EngineInterface::class)],
            'setColor()' => [Reference::to(ColorInterface::class)],
        ]);

        $this->methodsAndPropertiesDefinition = ArrayDefinition::fromConfig([
            'class' => Phone::class,
            '__construct()' => [
                'name' => 'Yii Phone',
                'version' => '3.0',
                'colors' => ['black', 'white'],
            ],
            '$dev' => true,
            '$codeName' => 'Radar',
            'addApp()' => ['Browser', '7'],
            'setId()' => ['42'],
        ]);

        $this->staticFactoryDefinition = new CallableDefinition(CarFactory::create(...));
        $this->serviceFactoryDefinition = new CallableDefinition([CarFactory::class, 'createWithColor']);
        $this->reference = Reference::to(EngineInterface::class);
    }

    /**
     * Measures explicit array definitions with constructor, setter, and reference resolution.
     *
     * @Groups({"definition", "lookup", "typical"})
     * @Revs(100)
     */
    public function benchArrayDefinitionObjectGraph(): void
    {
        $this->objectGraphDefinition->resolve($this->container);
    }

    /**
     * Measures constructor arguments, public property assignment, and method calls.
     *
     * @Groups({"definition", "lookup", "typical"})
     * @Revs(100)
     */
    public function benchArrayDefinitionMethodsAndProperties(): void
    {
        $this->methodsAndPropertiesDefinition->resolve($this->container);
    }

    /**
     * Measures a static callable factory with autowired arguments.
     *
     * @Groups({"factory", "lookup", "typical"})
     * @Revs(100)
     */
    public function benchStaticFactoryDefinition(): void
    {
        $this->staticFactoryDefinition->resolve($this->container);
    }

    /**
     * Measures a callable factory resolved as a service before invocation.
     *
     * @Groups({"factory", "lookup", "typical"})
     * @Revs(100)
     */
    public function benchServiceFactoryDefinition(): void
    {
        $this->serviceFactoryDefinition->resolve($this->container);
    }

    /**
     * Measures direct reference resolution against a PSR container.
     *
     * @Groups({"reference", "lookup", "typical"})
     */
    public function benchReferenceResolution(): void
    {
        $this->reference->resolve($this->container);
    }
}
