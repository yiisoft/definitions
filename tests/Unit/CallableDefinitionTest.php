<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Definitions\CallableDefinition;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\Tests\Objects\Car;
use Yiisoft\Definitions\Tests\Objects\CarFactory;
use Yiisoft\Definitions\Tests\Objects\ColorInterface;
use Yiisoft\Definitions\Tests\Objects\ColorPink;
use Yiisoft\Definitions\Tests\Support\SimpleDependencyResolver;

final class CallableDefinitionTest extends TestCase
{
    public function testDynamicCallable(): void
    {
        $definition = new CallableDefinition([CarFactory::class, 'createWithColor']);

        $dependencyResolver = new SimpleDependencyResolver(
            [
                CarFactory::class => new CarFactory(),
                ColorInterface::class => new ColorPink(),
            ]
        );

        /** @var Car $car */
        $car = $definition->resolve($dependencyResolver);

        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(ColorPink::class, $car->getColor());
    }

    public function testNonExistsClass(): void
    {
        $definition = new CallableDefinition(['NonExistsClass', 'run']);

        $this->expectException(NotInstantiableException::class);
        $this->expectExceptionMessage('Can not instantiate callable definition. Got array');
        $definition->resolve(new SimpleDependencyResolver());
    }
}
