<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit\Helpers;

use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Exception\NotInstantiableClassException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\Helpers\DefinitionExtractor;
use Yiisoft\Definitions\ParameterDefinition;
use Yiisoft\Definitions\Tests\Support\Car;
use Yiisoft\Definitions\Tests\Support\EngineInterface;
use Yiisoft\Definitions\Tests\Support\GearBox;
use Yiisoft\Definitions\Tests\Support\NullableConcreteDependency;
use Yiisoft\Definitions\Tests\Support\NullableInterfaceDependency;
use Yiisoft\Definitions\Tests\Support\OptionalConcreteDependency;
use Yiisoft\Definitions\Tests\Support\OptionalInterfaceDependency;
use Yiisoft\Definitions\Tests\Support\NullableOptionalConcreteDependency;
use Yiisoft\Definitions\Tests\Support\NullableOptionalInterfaceDependency;
use Yiisoft\Definitions\Tests\Support\SelfDependency;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class DefinitionExtractorTest extends TestCase
{
    public function testResolveConstructor(): void
    {
        if (PHP_VERSION_ID >= 80000) {
            $this->markTestSkipped('Can not determine default value of PHP internal parameters in PHP < 8.0.');
        }

        $container = new SimpleContainer();

        /** @var DefinitionInterface[] $dependencies */
        $dependencies = DefinitionExtractor::fromClassName(DateTime::class);

        // Since reflection for built-in classes does not get default values.
        $this->expectException(NotInstantiableException::class);
        $this->expectExceptionMessage(
            'Can not determine default value of parameter "time" when instantiating' .
            ' "DateTime::__construct()" because it is PHP internal. Please specify argument explicitly.'
        );
        $dependencies['time']->resolve($container);
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

    public function testOptionalInterfaceDependency(): void
    {
        $container = new SimpleContainer();
        /** @var DefinitionInterface[] $dependencies */
        $dependencies = DefinitionExtractor::fromClassName(OptionalInterfaceDependency::class);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(null, $dependencies['engine']->resolve($container));
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

    public function testOptionalConcreteDependency(): void
    {
        $container = new SimpleContainer();
        /** @var DefinitionInterface[] $dependencies */
        $dependencies = DefinitionExtractor::fromClassName(OptionalConcreteDependency::class);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(null, $dependencies['car']->resolve($container));
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
        /** @var DefinitionInterface[] $dependencies */
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
        $this->assertSame('self', $definition->getReflection()->getType()->getName());
    }
}
