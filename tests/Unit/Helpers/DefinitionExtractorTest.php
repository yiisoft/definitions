<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit\Helpers;

use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionFunction;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Exception\NotInstantiableClassException;
use Yiisoft\Definitions\Helpers\DefinitionExtractor;
use Yiisoft\Definitions\ParameterDefinition;
use Yiisoft\Definitions\Tests\Support\Car;
use Yiisoft\Definitions\Tests\Support\Chair;
use Yiisoft\Definitions\Tests\Support\ColorInterface;
use Yiisoft\Definitions\Tests\Support\EngineInterface;
use Yiisoft\Definitions\Tests\Support\EngineMarkOne;
use Yiisoft\Definitions\Tests\Support\GearBox;
use Yiisoft\Definitions\Tests\Support\NullableConcreteDependency;
use Yiisoft\Definitions\Tests\Support\NullableInterfaceDependency;
use Yiisoft\Definitions\Tests\Support\NullableOptionalConcreteDependency;
use Yiisoft\Definitions\Tests\Support\NullableOptionalInterfaceDependency;
use Yiisoft\Definitions\Tests\Support\RedChair;
use Yiisoft\Definitions\Tests\Support\SelfDependency;
use Yiisoft\Definitions\Tests\Support\UnionCar;
use Yiisoft\Definitions\Tests\Support\UnionSelfDependency;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class DefinitionExtractorTest extends TestCase
{
    public function testResolveConstructor(): void
    {
        $container = new SimpleContainer();

        /** @var DefinitionInterface[] $dependencies */
        $dependencies = DefinitionExtractor::fromClassName(DateTime::class);

        $this->assertCount(2, $dependencies);
        $this->assertEquals('now', $dependencies['datetime']->resolve($container));
        $this->assertEquals(null, $dependencies['timezone']->resolve($container));
    }

    public function testResolveUnionCarConstructor(): void
    {
        $container = new SimpleContainer([
            EngineMarkOne::class => new EngineMarkOne(),
        ]);

        $dependencies = DefinitionExtractor::fromClassName(UnionCar::class);

        $this->assertCount(1, $dependencies);
        $this->assertInstanceOf(ParameterDefinition::class, $dependencies['engine']);
        $resolved = $dependencies['engine']->resolve($container);
        $this->assertInstanceOf(EngineMarkOne::class, $resolved);
    }

    public function testUnionScalarTypes(): void
    {
        $definition = DefinitionExtractor::fromFunction(
            new ReflectionFunction(static fn(string|int $a): bool => true),
        )['a'];

        $this->assertInstanceOf(ParameterDefinition::class, $definition);
    }

    public function testFromClassWithUnionSelfDependency(): void
    {
        $definition = DefinitionExtractor::fromClassName(UnionSelfDependency::class)['a'];

        $actualType = implode('|', $definition
            ->getReflection()
            ->getType()
            ->getTypes());
        $this->assertInstanceOf(ParameterDefinition::class, $definition);
        $this->assertSame('self|' . ColorInterface::class, $actualType);
    }

    public function testResolveCarConstructor(): void
    {
        $container = new SimpleContainer();
        /** @var DefinitionInterface[] $dependencies */
        $dependencies = DefinitionExtractor::fromClassName(Car::class);

        $this->assertCount(2, $dependencies);
        $this->assertInstanceOf(ParameterDefinition::class, $dependencies['engine']);
        $this->assertInstanceOf(ParameterDefinition::class, $dependencies['moreEngines']);

        $this->expectException(NotFoundExceptionInterface::class);
        $dependencies['engine']->resolve($container);
    }

    public function testResolveGearBoxConstructor(): void
    {
        $container = new SimpleContainer();
        /** @var DefinitionInterface[] $dependencies */
        $dependencies = DefinitionExtractor::fromClassName(GearBox::class);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(5, $dependencies['maxGear']->resolve($container));
    }

    public function testNullableInterfaceDependency(): void
    {
        $container = new SimpleContainer();
        /** @var DefinitionInterface[] $dependencies */
        $dependencies = DefinitionExtractor::fromClassName(NullableInterfaceDependency::class);
        $this->assertCount(1, $dependencies);
        $this->expectException(NotFoundExceptionInterface::class);
        $dependencies['engine']->resolve($container);
    }

    public function testNullableConcreteDependency(): void
    {
        $container = new SimpleContainer();
        /** @var DefinitionInterface[] $dependencies */
        $dependencies = DefinitionExtractor::fromClassName(NullableConcreteDependency::class);
        $this->assertCount(1, $dependencies);
        $this->expectException(NotFoundExceptionInterface::class);
        $dependencies['car']->resolve($container);
    }

    public function testNullableOptionalConcreteDependency(): void
    {
        $container = new SimpleContainer();
        /** @var DefinitionInterface[] $dependencies */
        $dependencies = DefinitionExtractor::fromClassName(NullableOptionalConcreteDependency::class);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(null, $dependencies['car']->resolve($container));
    }

    public function testNullableOptionalInterfaceDependency(): void
    {
        $container = new SimpleContainer();
        $dependencies = DefinitionExtractor::fromClassName(NullableOptionalInterfaceDependency::class);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(null, $dependencies['engine']->resolve($container));
    }

    public function testFromNonExistingClass(): void
    {
        $this->expectException(NotInstantiableClassException::class);
        $this->expectExceptionMessage('Can not instantiate NonExistingClass.');
        DefinitionExtractor::fromClassName('NonExistingClass');
    }

    public function testFromNotInstantiableClass(): void
    {
        $this->expectException(NotInstantiableClassException::class);
        $this->expectExceptionMessage('Can not instantiate ' . EngineInterface::class . '.');
        DefinitionExtractor::fromClassName(EngineInterface::class);
    }

    public function testFromClassWithSelfDependency(): void
    {
        /** @var ParameterDefinition $definition */
        $definition = DefinitionExtractor::fromClassName(SelfDependency::class)['a'];

        $this->assertInstanceOf(ParameterDefinition::class, $definition);
        $this->assertSame(
            'self',
            $definition
                ->getReflection()
                ->getType()
                ->getName(),
        );
    }

    public function testResolvableDependencyWithDefaultObject(): void
    {
        $container = new SimpleContainer([
            Chair::class => new Chair(),
        ]);

        $definitions = DefinitionExtractor::fromFunction(
            new ReflectionFunction(static fn(Chair $chair = new RedChair()) => true),
        );

        $this->assertInstanceOf(Chair::class, $definitions['chair']->resolve($container));
    }

    public function testResolvableNullableDependencyWithDefaultObject(): void
    {
        $container = new SimpleContainer([
            Chair::class => new Chair(),
        ]);

        $definitions = DefinitionExtractor::fromFunction(
            new ReflectionFunction(static fn(?Chair $chair = new RedChair()) => true),
        );

        $this->assertInstanceOf(Chair::class, $definitions['chair']->resolve($container));
    }

    public function testUnresolvableDependencyWithDefaultObject(): void
    {
        $container = new SimpleContainer();

        $definitions = DefinitionExtractor::fromFunction(
            new ReflectionFunction(static fn(Chair $chair = new RedChair()) => true),
        );

        $this->assertInstanceOf(RedChair::class, $definitions['chair']->resolve($container));
    }

    public function testUnresolvablNullableDependencyWithDefaultObject(): void
    {
        $container = new SimpleContainer();

        $definitions = DefinitionExtractor::fromFunction(
            new ReflectionFunction(static fn(?Chair $chair = new RedChair()) => true),
        );

        $this->assertInstanceOf(RedChair::class, $definitions['chair']->resolve($container));
    }
}
