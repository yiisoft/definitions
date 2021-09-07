<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Definitions\CallableDefinition;
use Yiisoft\Definitions\Exception\NotFoundException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\Tests\Objects\Car;
use Yiisoft\Definitions\Tests\Objects\CarFactory;
use Yiisoft\Definitions\Tests\Objects\ColorInterface;
use Yiisoft\Definitions\Tests\Objects\ColorPink;
use Yiisoft\Definitions\Tests\Support\SimpleDependencyResolver;
use Yiisoft\Injector\Injector;

final class CallableDefinitionTest extends TestCase
{
    public function testDynamicCallable(): void
    {
        $definition = new CallableDefinition([CarFactory::class, 'createWithColor']);

        $dependencyResolver = new SimpleDependencyResolver(
            [
                CarFactory::class => new CarFactory(),
                ColorInterface::class => new ColorPink(),
            ],
            static function (string $id) use (&$container) {
                if ($id === Injector::class) {
                    return new Injector($container);
                }
                throw new NotFoundException($id);
            }
        );

        /** @var Car $car */
        $car = $definition->resolve($dependencyResolver);

        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(ColorPink::class, $car->getColor());
    }

    public function testReflectionException(): void
    {
        $definition = new CallableDefinition(['NonExistsClass', 'run']);

        $this->expectException(NotInstantiableException::class);
        $this->expectExceptionMessage('Can not instantiate callable definition. Got array');
        $definition->resolve(new SimpleDependencyResolver());
    }
}
