<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit\Infrastructure;

use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Exception\NotInstantiableClassException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\Infrastructure\DefinitionExtractor;
use Yiisoft\Definitions\ParameterDefinition;
use Yiisoft\Definitions\Tests\Objects\Car;
use Yiisoft\Definitions\Tests\Objects\EngineInterface;
use Yiisoft\Definitions\Tests\Objects\GearBox;
use Yiisoft\Definitions\Tests\Objects\NullableConcreteDependency;
use Yiisoft\Definitions\Tests\Objects\NullableInterfaceDependency;
use Yiisoft\Definitions\Tests\Objects\OptionalConcreteDependency;
use Yiisoft\Definitions\Tests\Objects\OptionalInterfaceDependency;
use Yiisoft\Definitions\Tests\Objects\NullableOptionalConcreteDependency;
use Yiisoft\Definitions\Tests\Objects\NullableOptionalInterfaceDependency;
use Yiisoft\Definitions\Tests\Objects\SelfDependency;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class DefinitionExtractorTest extends TestCase
{
    public function testResolveConstructor(): void
    {
        if (PHP_VERSION_ID >= 80000) {
            $this->markTestSkipped('Can not determine default value of PHP internal parameters in PHP < 8.0.');
        }

        $resolver = DefinitionExtractor::getInstance();
        $container = new SimpleContainer();

        /** @var DefinitionInterface[] $dependencies */
        $dependencies = $resolver->fromClassName(DateTime::class);

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
        $resolver = DefinitionExtractor::getInstance();
        $container = new SimpleContainer();
        /** @var DefinitionInterface[] $dependencies */
        $dependencies = $resolver->fromClassName(Car::class);

        $this->assertCount(2, $dependencies);
        $this->assertInstanceOf(ParameterDefinition::class, $dependencies['engine']);
        $this->assertInstanceOf(ParameterDefinition::class, $dependencies['moreEngines']);

        $this->expectException(NotFoundExceptionInterface::class);
        $dependencies['engine']->resolve($container);
    }

    public function testResolveGearBoxConstructor(): void
    {
        $resolver = DefinitionExtractor::getInstance();
        $container = new SimpleContainer();
        /** @var DefinitionInterface[] $dependencies */
        $dependencies = $resolver->fromClassName(GearBox::class);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(5, $dependencies['maxGear']->resolve($container));
    }

    public function testOptionalInterfaceDependency(): void
    {
        $resolver = DefinitionExtractor::getInstance();
        $container = new SimpleContainer();
        /** @var DefinitionInterface[] $dependencies */
        $dependencies = $resolver->fromClassName(OptionalInterfaceDependency::class);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(null, $dependencies['engine']->resolve($container));
    }

    public function testNullableInterfaceDependency(): void
    {
        $resolver = DefinitionExtractor::getInstance();
        $container = new SimpleContainer();
        /** @var DefinitionInterface[] $dependencies */
        $dependencies = $resolver->fromClassName(NullableInterfaceDependency::class);
        $this->assertCount(1, $dependencies);
        $this->expectException(NotFoundExceptionInterface::class);
        $dependencies['engine']->resolve($container);
    }

    public function testOptionalConcreteDependency(): void
    {
        $resolver = DefinitionExtractor::getInstance();
        $container = new SimpleContainer();
        /** @var DefinitionInterface[] $dependencies */
        $dependencies = $resolver->fromClassName(OptionalConcreteDependency::class);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(null, $dependencies['car']->resolve($container));
    }

    public function testNullableConcreteDependency(): void
    {
        $resolver = DefinitionExtractor::getInstance();
        $container = new SimpleContainer();
        /** @var DefinitionInterface[] $dependencies */
        $dependencies = $resolver->fromClassName(NullableConcreteDependency::class);
        $this->assertCount(1, $dependencies);
        $this->expectException(NotFoundExceptionInterface::class);
        $dependencies['car']->resolve($container);
    }

    public function testNullableOptionalConcreteDependency(): void
    {
        $resolver = DefinitionExtractor::getInstance();
        $container = new SimpleContainer();
        /** @var DefinitionInterface[] $dependencies */
        $dependencies = $resolver->fromClassName(NullableOptionalConcreteDependency::class);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(null, $dependencies['car']->resolve($container));
    }

    public function testNullableOptionalInterfaceDependency(): void
    {
        $resolver = DefinitionExtractor::getInstance();
        $container = new SimpleContainer();
        /** @var DefinitionInterface[] $dependencies */
        $dependencies = $resolver->fromClassName(NullableOptionalInterfaceDependency::class);
        $this->assertCount(1, $dependencies);
        $this->assertEquals(null, $dependencies['engine']->resolve($container));
    }

    public function testFromNonExistingClass(): void
    {
        $extractor = DefinitionExtractor::getInstance();

        $this->expectException(NotInstantiableClassException::class);
        $this->expectExceptionMessage('Can not instantiate NonExistingClass.');
        $extractor->fromClassName('NonExistingClass');
    }

    public function testFromNotInstantiableClass(): void
    {
        $extractor = DefinitionExtractor::getInstance();

        $this->expectException(NotInstantiableClassException::class);
        $this->expectExceptionMessage('Can not instantiate ' . EngineInterface::class . '.');
        $extractor->fromClassName(EngineInterface::class);
    }

    public function testFromClassWithSelfDependency(): void
    {
        /** @var ParameterDefinition $definition */
        $definition = DefinitionExtractor::getInstance()->fromClassName(SelfDependency::class)['a'];

        $this->assertInstanceOf(ParameterDefinition::class, $definition);
        $this->assertSame('self', $definition->getReflection()->getType()->getName());
    }
}
