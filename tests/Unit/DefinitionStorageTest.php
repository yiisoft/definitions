<?php

declare(strict_types=1);

namespace Yiisoft\Dfinitions\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Definitions\DefinitionStorage;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\Tests\FunBike;
use Yiisoft\Definitions\Tests\Support\Bike;
use Yiisoft\Definitions\Tests\Support\Car;
use Yiisoft\Definitions\Tests\Support\Circular\Chicken;
use Yiisoft\Definitions\Tests\Support\Circular\Egg;
use Yiisoft\Definitions\Tests\Support\ColorInterface;
use Yiisoft\Definitions\Tests\Support\ColorPink;
use Yiisoft\Definitions\Tests\Support\DefinitionStorage\ServiceWithBuiltinTypeWithoutDefault;
use Yiisoft\Definitions\Tests\Support\DefinitionStorage\ServiceWithNonExistingSubDependency;
use Yiisoft\Definitions\Tests\Support\DefinitionStorage\ServiceWithNonExistingDependency;
use Yiisoft\Definitions\Tests\Support\DefinitionStorage\ServiceWithNonResolvableUnionTypes;
use Yiisoft\Definitions\Tests\Support\DefinitionStorage\ServiceWithPrivateConstructor;
use Yiisoft\Definitions\Tests\Support\DefinitionStorage\ServiceWithPrivateConstructorSubDependency;
use Yiisoft\Definitions\Tests\Support\EngineInterface;
use Yiisoft\Definitions\Tests\Support\EngineMarkOne;
use Yiisoft\Definitions\Tests\Support\Mechanism;
use Yiisoft\Definitions\Tests\Support\Notebook;
use Yiisoft\Definitions\Tests\Support\SelfDependency;
use Yiisoft\Definitions\Tests\Support\Tree;
use Yiisoft\Definitions\Tests\Support\UnionCar;
use Yiisoft\Definitions\Tests\Support\UnionSelfDependency;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class DefinitionStorageTest extends TestCase
{
    public function testUnresolvableUnionSelfDependency(): void
    {
        $storage = new DefinitionStorage();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Service ' . UnionSelfDependency::class . ' doesn\'t exist in DefinitionStorage.'
        );
        $storage->get(UnionSelfDependency::class);
    }

    public function testResolvableUnionTypeDependency(): void
    {
        $storage = new DefinitionStorage();

        $definition = $storage->get(UnionCar::class);

        $this->assertSame(UnionCar::class, $definition);
    }

    public function testResolvableFromContainerUnionTypeDependency(): void
    {
        $storage = new DefinitionStorage();

        $container = new SimpleContainer([
            ColorInterface::class => new ColorPink(),
        ]);
        $storage->setDelegateContainer($container);

        $definition = $storage->get(UnionSelfDependency::class);

        $this->assertSame(UnionSelfDependency::class, $definition);
    }

    public function testExplicitDefinitionIsNotChecked(): void
    {
        $storage = new DefinitionStorage(['existing' => 'anything']);
        $this->assertTrue($storage->has('existing'));
        $this->assertSame([], $storage->getBuildStack());
    }

    public static function dataParameterNameBindings(): iterable
    {
        yield 'untyped reference' => [
            [
                '$engine' => new EngineMarkOne(),
                '$color' => 'red',
                Bike::class => Bike::class,
            ],
            Bike::class,
        ];

        yield 'typed reference' => [
            [
                EngineInterface::class . ' $engine' => new EngineMarkOne(),
                ColorInterface::class . ' $color' => new ColorPink(),
                Bike::class => Bike::class,
            ],
            Bike::class,
        ];

        yield 'referenced reference' => [
            [
                EngineInterface::class . ' $engine' => new EngineMarkOne(),
                ColorInterface::class . ' $color' => Reference::to(ColorPink::class),
                ColorPink::class => ColorPink::class,
                Bike::class => Bike::class,
            ],
            Bike::class,
        ];
    }

    /**
     * @dataProvider dataParameterNameBindings
     */
    public function testParameterNameBindings(array $definitions, string $class): void
    {
        $storage = new DefinitionStorage($definitions);
        $this->assertTrue($storage->has($class));
    }

    public function testNonExistingService(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(NonExisitng::class));
        $this->assertSame([NonExisitng::class], $storage->getBuildStack());
    }

    public function testServiceWithNonExistingDependency(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithNonExistingDependency::class));
        $this->assertSame(
            [
                ServiceWithNonExistingDependency::class,
                \NonExisting::class,
            ],
            $storage->getBuildStack()
        );
    }

    public function testServiceWithNonExistingSubDependency(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithNonExistingSubDependency::class));
        $this->assertSame(
            [
                ServiceWithNonExistingSubDependency::class,
                ServiceWithNonExistingDependency::class ,
                \NonExisting::class,
            ],
            $storage->getBuildStack()
        );
    }

    public function testServiceWithPrivateConstructor(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithPrivateConstructor::class));
        $this->assertSame([ServiceWithPrivateConstructor::class], $storage->getBuildStack());
    }

    public function testServiceWithPrivateConstructorSubDependency(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithPrivateConstructorSubDependency::class));
        $this->assertSame(
            [
                ServiceWithPrivateConstructorSubDependency::class,
                ServiceWithPrivateConstructor::class,
            ],
            $storage->getBuildStack()
        );
    }

    public function testNotExistsUnionTypes(): void
    {
        $storage = new DefinitionStorage();
        $this->assertFalse($storage->has(Notebook::class));
        $this->assertSame(
            [
                Notebook::class,
                \NotExist1::class,
                \NotExist2::class,
            ],
            $storage->getBuildStack()
        );
    }

    public function testServiceWithBuiltInTypeWithoutDefault(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithBuiltinTypeWithoutDefault::class));
        $this->assertSame([ServiceWithBuiltinTypeWithoutDefault::class], $storage->getBuildStack());
    }

    public function testEmptyDelegateContainer(): void
    {
        $container = new SimpleContainer([]);
        $storage = new DefinitionStorage([]);
        $storage->setDelegateContainer($container);
        $this->assertFalse($storage->has(\NonExisitng::class));
        $this->assertSame([\NonExisitng::class], $storage->getBuildStack());
    }

    public function testServiceWithNonExistingUnionTypes(): void
    {
        $storage = new DefinitionStorage([]);
        $this->assertFalse($storage->has(ServiceWithNonResolvableUnionTypes::class));
        $this->assertSame(
            [
                ServiceWithNonResolvableUnionTypes::class,
                ServiceWithNonExistingDependency::class,
                \NonExisting::class,
                ServiceWithPrivateConstructor::class,
            ],
            $storage->getBuildStack()
        );
    }

    public function testStrictModeDisabled(): void
    {
        $storage = new DefinitionStorage([], false);
        $this->assertTrue($storage->has(EngineMarkOne::class));

        $storage = new DefinitionStorage([], false);
        $this->assertSame(EngineMarkOne::class, $storage->get(EngineMarkOne::class));
    }

    public function testStrictModeEnabled(): void
    {
        $storage = new DefinitionStorage([], true);
        $this->assertFalse($storage->has(EngineMarkOne::class));

        $storage = new DefinitionStorage([], true);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Service ' . EngineMarkOne::class . ' doesn\'t exist in DefinitionStorage.');
        $storage->get(EngineMarkOne::class);
    }

    public function testSet(): void
    {
        $storage = new DefinitionStorage();
        $storage->set(ColorInterface::class, ColorPink::class);

        $definition = $storage->get(ColorInterface::class);

        $this->assertSame(ColorPink::class, $definition);
    }

    public function testCircular(): void
    {
        $storage = new DefinitionStorage();

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessage(
            'Circular reference to "' . Chicken::class . '" detected while building: ' .
            Chicken::class . ', ' . Egg::class
        );
        $storage->get(Chicken::class);
    }

    public function testSelfCircular(): void
    {
        $storage = new DefinitionStorage();

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessage(
            'Circular reference to "' . SelfDependency::class . '" detected while building: ' . SelfDependency::class
        );
        $storage->get(SelfDependency::class);
    }

    public function testBuildStackOnThrowException(): void
    {
        $stack = null;
        $storage = new DefinitionStorage();

        try {
            $storage->get(Chicken::class);
        } catch (CircularReferenceException) {
            $stack = $storage->getBuildStack();
        }

        $this->assertSame([Chicken::class, Egg::class], $stack);
    }

    public function testWithoutDependencies(): void
    {
        $storage = new DefinitionStorage();

        $object = $storage->get(ColorPink::class);

        $this->assertSame(ColorPink::class, $object);
    }

    public function dataHas(): array
    {
        return [
            [false, Tree::class, [], new SimpleContainer([ColorInterface::class => new ColorPink()])],
            [false, FunBike::class, [], new SimpleContainer([EngineInterface::class => new EngineMarkOne()])],
            [false, Bike::class, [ColorInterface::class => ColorPink::class]],
            [false, Mechanism::class, [], new SimpleContainer([ColorInterface::class => new ColorPink()])],
            [true, Car::class, [EngineInterface::class => EngineMarkOne::class]],
        ];
    }

    /**
     * @dataProvider dataHas
     */
    public function testHas(
        bool $expected,
        string $id,
        array $definitions = [],
        ?ContainerInterface $delegateContainer = null,
    ): void {
        $storage = new DefinitionStorage($definitions);

        if ($delegateContainer !== null) {
            $storage->setDelegateContainer($delegateContainer);
        }

        $this->assertSame($expected, $storage->has($id));
    }
}
