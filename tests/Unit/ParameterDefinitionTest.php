<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit;

use Closure;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use RuntimeException;
use stdClass;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\ParameterDefinition;
use Yiisoft\Definitions\Tests\Support\CircularReferenceExceptionDependency;
use Yiisoft\Definitions\Tests\Support\GearBox;
use Yiisoft\Definitions\Tests\Support\RuntimeExceptionDependency;
use Yiisoft\Definitions\Tests\Support\Car;
use Yiisoft\Definitions\Tests\Support\NullableConcreteDependency;
use Yiisoft\Definitions\Tests\Support\SelfDependency;
use Yiisoft\Definitions\Tests\Support\UnionBuiltinDependency;
use Yiisoft\Definitions\Tests\Support\UnionCar;
use Yiisoft\Definitions\Tests\Support\UnionOptionalDependency;
use Yiisoft\Definitions\Tests\Support\UnionSelfDependency;
use Yiisoft\Test\Support\Container\Exception\NotFoundException;
use Yiisoft\Test\Support\Container\SimpleContainer;

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
            [false, $parameters[1]],
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

    public function testResolveWithIncorrectTypeInContainer(): void
    {
        $definition = new ParameterDefinition($this->getFirstParameter(fn (stdClass $class) => true));

        $container = new SimpleContainer([stdClass::class => 42]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Container returned incorrect type "integer" for service "' . stdClass::class . '".'
        );
        $definition->resolve($container);
    }

    public function testNullableParameterNotInstantiable(): void
    {
        $definition = new ParameterDefinition(
            (new ReflectionClass(NullableConcreteDependency::class))
                ->getConstructor()
                ->getParameters()[0]
        );
        $container = new SimpleContainer();

        $this->expectException(NotFoundExceptionInterface::class);
        $definition->resolve($container);
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
            'nullableAndDefaultNull' => [
                null,
                $this->getFirstParameter(static fn (?int $n = null) => true),
            ],
        ];
    }

    /**
     * @dataProvider dataResolve
     */
    public function testResolve($expected, ReflectionParameter $parameter): void
    {
        $definition = new ParameterDefinition($parameter);
        $container = new SimpleContainer();

        $this->assertSame($expected, $definition->resolve($container));
    }

    public function testResolveNonTypedParameter(): void
    {
        $definition = new ParameterDefinition(
            $this->getFirstParameter(
                static fn ($x) => true,
            )
        );
        $container = new SimpleContainer();

        $this->expectException(NotInstantiableException::class);
        $this->expectExceptionMessage(
            'Can not determine value of the "x" parameter without type when instantiating'
        );
        $definition->resolve($container);
    }

    public function testResolveBuiltinParameter(): void
    {
        $definition = new ParameterDefinition(
            $this->getFirstParameter(
                static fn (int $n) => true,
            )
        );
        $container = new SimpleContainer();

        $this->expectException(NotInstantiableException::class);
        $this->expectExceptionMessage(
            'Can not determine value of the "n" parameter of type "int" when instantiating'
        );
        $definition->resolve($container);
    }

    public function testResolveSelf(): void
    {
        $definition = new ParameterDefinition(
            $this->getFirstConstructorParameter(SelfDependency::class)
        );
        $container = new SimpleContainer();

        $this->expectException(NotFoundException::class);
        $definition->resolve($container);
    }

    public function testNotInstantiable(): void
    {
        $definition = new ParameterDefinition(
            (new ReflectionClass(Car::class))
                ->getConstructor()
                ->getParameters()[0]
        );
        $container = new SimpleContainer();

        $this->expectException(NotFoundExceptionInterface::class);
        $definition->resolve($container);
    }

    public function testNotInstantiableWithUnionType(): void
    {
        $definition = new ParameterDefinition(
            (new ReflectionClass(UnionCar::class))
                ->getConstructor()
                ->getParameters()[0]
        );
        $container = new SimpleContainer();

        $this->expectException(NotFoundExceptionInterface::class);
        $definition->resolve($container);
    }

    public function testNotInstantiablePhpInternal(): void
    {
        if (PHP_VERSION_ID >= 80000) {
            $this->markTestSkipped('Can not determine default value of PHP internal parameters in PHP < 8.0.');
        }

        $definition = new ParameterDefinition(
            $this->getParameters('trim')[1]
        );
        $container = new SimpleContainer();

        $this->expectException(NotInstantiableException::class);
        $this->expectExceptionMessage(
            'Can not determine default value of parameter "character_mask" when instantiating "trim()" ' .
            'because it is PHP internal. Please specify argument explicitly.'
        );
        $definition->resolve($container);
    }

    public function testOptionalBrokenDependency(): void
    {
        $container = new SimpleContainer(
            [],
            static function (string $id) {
                if ($id === RuntimeExceptionDependency::class) {
                    return new RuntimeExceptionDependency();
                }
                throw new NotFoundException($id);
            },
            static fn (string $id): bool => $id === RuntimeExceptionDependency::class
        );
        $definition = new ParameterDefinition(
            $this->getFirstParameter(static fn (?RuntimeExceptionDependency $d = null) => 42),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Broken.');
        $definition->resolve($container);
    }

    public function testOptionalCircularDependency(): void
    {
        $container = new SimpleContainer(
            [],
            static function (string $id) {
                if ($id === CircularReferenceExceptionDependency::class) {
                    return new CircularReferenceExceptionDependency();
                }
                throw new NotFoundException($id);
            },
            static fn (string $id): bool => $id === CircularReferenceExceptionDependency::class
        );
        $definition = new ParameterDefinition(
            $this->getFirstParameter(static fn (?CircularReferenceExceptionDependency $d = null) => 42),
        );

        $result = $definition->resolve($container);

        $this->assertNull($result);
    }

    public function testResolveUnionType(): void
    {
        $container = new SimpleContainer([
            stdClass::class => new stdClass(),
        ]);

        $definition = new ParameterDefinition(
            $this->getFirstParameter(fn (GearBox|stdClass $class) => true)
        );
        $result = $definition->resolve($container);

        $this->assertInstanceOf(stdClass::class, $result);
    }

    public function testResolveRequiredUnionTypeWithIncorrectTypeInContainer(): void
    {
        $class = GearBox::class . '|' . stdClass::class;

        $definition = new ParameterDefinition(
            $this->getFirstParameter(fn (GearBox|stdClass $class) => true)
        );

        $container = new SimpleContainer([
            GearBox::class => 7,
            stdClass::class => new stdClass(),
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

    public function testResolveOptionalBrokenDependencyWithUnionTypes(): void
    {
        $container = new SimpleContainer(
            [],
            static function (string $id) {
                if ($id === RuntimeExceptionDependency::class) {
                    return new RuntimeExceptionDependency();
                }
                throw new NotFoundException($id);
            },
            static fn (string $id): bool => $id === RuntimeExceptionDependency::class
        );
        $definition = new ParameterDefinition(
            $this->getFirstParameter(static fn (RuntimeExceptionDependency|string|null $d = null) => 42),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Broken.');
        $definition->resolve($container);
    }

    public function testResolveOptionalCircularDependencyWithUnionTypes(): void
    {
        $container = new SimpleContainer(
            [],
            static function (string $id) {
                if ($id === CircularReferenceExceptionDependency::class) {
                    return new CircularReferenceExceptionDependency();
                }
                throw new NotFoundException($id);
            },
            static fn (string $id): bool => $id === CircularReferenceExceptionDependency::class
        );
        $definition = new ParameterDefinition(
            $this->getFirstParameter(static fn (CircularReferenceExceptionDependency|string|null $d = null) => 42),
        );

        $result = $definition->resolve($container);

        $this->assertNull($result);
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
