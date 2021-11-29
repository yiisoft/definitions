<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Php8;

use PHPUnit\Framework\TestCase;
use Closure;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use stdClass;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\ParameterDefinition;
use Yiisoft\Definitions\Tests\Support\GearBox;
use Yiisoft\Definitions\Tests\Support\UnionBuiltinDependency;
use Yiisoft\Definitions\Tests\Support\UnionCar;
use Yiisoft\Definitions\Tests\Support\UnionOptionalDependency;
use Yiisoft\Definitions\Tests\Support\UnionSelfDependency;
use Yiisoft\Test\Support\Container\Exception\NotFoundException;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class ParameterDefinitionTest extends TestCase
{
    public function testNotInstantiable(): void
    {
        $definition = new ParameterDefinition(
            (new ReflectionClass(UnionCar::class))->getConstructor()->getParameters()[0]
        );
        $container = new SimpleContainer();

        $this->expectException(NotFoundExceptionInterface::class);
        $definition->resolve($container);
    }

    public function testResolveRequiredUnionTypeWithIncorrectTypeInContainer(): void
    {
        $class = stdClass::class . '|' . GearBox::class;

        $definition = new ParameterDefinition($this->getFirstParameter(fn (stdClass|GearBox $class) => true));

        $container = new SimpleContainer([
            stdClass::class => 42,
            GearBox::class => 7,
        ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Container returned incorrect type "integer" for service "' . $class . '".'
        );
        $definition->resolve($container);
    }

    public function testResolveOptionalUnionTypeWithIncorrectTypeInContainer(): void
    {
        $this->markTestSkipped(
            'Is there a real case?'
        );
        $class = stdClass::class . '|' . GearBox::class;

        $definition = new ParameterDefinition($this->getFirstParameter(fn (stdClass|GearBox $class) => true));

        $container = new SimpleContainer([
            stdClass::class => 42,
            GearBox::class => 7,
        ]);

        $result = $definition->resolve($container);

        $this->assertNull($result);
    }

    public function testResolveOptionalUnionType(): void
    {
        $definition = new ParameterDefinition(
            $this->getFirstConstructorParameter(UnionOptionalDependency::class)
        );
        $container = new SimpleContainer();

        $this->assertNull($definition->resolve($container));
    }

    public function testResolveUnionBuiltin(): void
    {
        $definition = new ParameterDefinition(
            $this->getFirstConstructorParameter(UnionBuiltinDependency::class)
        );
        $container = new SimpleContainer();

        $this->expectException(NotInstantiableException::class);
        $this->expectExceptionMessage(
            'Can not determine value of the "value" parameter of type "string|int" when instantiating '
        );
        $definition->resolve($container);
    }

    public function testResolveUnionSelf(): void
    {
        $definition = new ParameterDefinition(
            $this->getFirstConstructorParameter(UnionSelfDependency::class)
        );
        $container = new SimpleContainer();

        $this->expectException(NotFoundException::class);
        $definition->resolve($container);
    }

    /**
     * @return ReflectionParameter[]
     */
    private function getParameters(callable $callable): array
    {
        $closure = $callable instanceof Closure ? $callable : Closure::fromCallable($callable);
        return (new ReflectionFunction($closure))->getParameters();
    }

    private function getFirstParameter(Closure $closure): ReflectionParameter
    {
        return $this->getParameters($closure)[0];
    }

    private function getFirstConstructorParameter(string $class): ReflectionParameter
    {
        return (new ReflectionClass($class))
            ->getConstructor()
            ->getParameters()[0];
    }
}
