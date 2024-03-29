<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Php8_1;

use Closure;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionParameter;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\ParameterDefinition;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class ParameterDefinitionTest extends TestCase
{
    public function testNotResolveIntersectionType(): void
    {
        $container = new SimpleContainer();

        $definition = new ParameterDefinition(
            $this->getFirstParameter(fn (GearBox&stdClass $class) => true)
        );

        $this->expectException(NotInstantiableException::class);
        $this->expectExceptionMessage('Can not determine value of the "class" parameter of type ');
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
}
