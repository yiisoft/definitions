<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit;

use Closure;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\ParameterDefinition;
use Yiisoft\Definitions\Tests\Objects\Car;
use Yiisoft\Definitions\Tests\Objects\EngineInterface;
use Yiisoft\Definitions\Tests\Support\SimpleDependencyResolver;

final class ParameterDefinitionTest extends TestCase
{
    public function dataIsVariadic(): array
    {
        $parameters = $this->getParameters(
            static fn (
                string $a,
                string ...$b
            ): bool => true
        );

        return [
            [false, $parameters[0]],
            [true, $parameters[1]],
        ];
    }

    /**
     * @dataProvider dataIsVariadic
     */
    public function testIsVariadic(bool $expected, ReflectionParameter $parameter): void
    {
        $definition = new ParameterDefinition($parameter);

        $this->assertSame($expected, $definition->isVariadic());
    }

    public function dataIsOptional(): array
    {
        $parameters = $this->getParameters(
            static fn (
                string $a,
                string $b = 'b'
            ): bool => true
        );

        return [
            [false, $parameters[0]],
            [true, $parameters[1]],
        ];
    }

    /**
     * @dataProvider dataIsOptional
     */
    public function testIsOptional(bool $expected, ReflectionParameter $parameter): void
    {
        $definition = new ParameterDefinition($parameter);

        $this->assertSame($expected, $definition->isOptional());
    }

    public function dataHasValue(): array
    {
        $parameters = $this->getParameters(
            static fn (
                string $a,
                ?string $b,
                string $c = null,
                string $d = 'hello'
            ): bool => true
        );

        return [
            [false, $parameters[0]],
            [true, $parameters[1]],
            [true, $parameters[2]],
            [true, $parameters[3]],
        ];
    }

    /**
     * @dataProvider dataHasValue
     */
    public function testHasValue(bool $expected, ReflectionParameter $parameter): void
    {
        $definition = new ParameterDefinition($parameter);

        $this->assertSame($expected, $definition->hasValue());
    }

    public function dataResolve(): array
    {
        return [
            'defaultValue' => [
                7,
                $this->getFirstParameter(static fn (int $n = 7) => true),
            ],
            'defaultNull' => [
                null,
                $this->getFirstParameter(static fn (int $n = null) => true),
            ],
            'nullableType' => [
                null,
                $this->getFirstParameter(static fn (?int $n) => true),
            ],
        ];
    }

    /**
     * @dataProvider dataResolve
     */
    public function testResolve($expected, ReflectionParameter $parameter): void
    {
        $definition = new ParameterDefinition($parameter);
        $dependencyResolver = new SimpleDependencyResolver();

        $this->assertSame($expected, $definition->resolve($dependencyResolver));
    }

    public function testNotInstantiable(): void
    {
        $definition = new ParameterDefinition(
            (new ReflectionClass(Car::class))->getConstructor()->getParameters()[0]
        );
        $dependencyResolver = new SimpleDependencyResolver();

        $this->expectException(NotInstantiableException::class);
        $this->expectExceptionMessage(
            'Can not determine value of the "engine" parameter of type "' .
            EngineInterface::class .
            '" when instantiating "' .
            Car::class . '::__construct()"' .
            '. Please specify argument explicitly.'
        );
        $definition->resolve($dependencyResolver);
    }

    public function testNotInstantiablePhpInternal(): void
    {
        if (PHP_VERSION_ID >= 80000) {
            $this->markTestSkipped('Can not determine default value of PHP internal parameters in PHP < 8.0.');
        }

        $definition = new ParameterDefinition(
            $this->getParameters('trim')[1]
        );
        $dependencyResolver = new SimpleDependencyResolver();

        $this->expectException(NotInstantiableException::class);
        $this->expectExceptionMessage(
            'Can not determine default value of parameter "character_mask" when instantiating "trim()" ' .
            'because it is PHP internal. Please specify argument explicitly.'
        );
        $definition->resolve($dependencyResolver);
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
}
