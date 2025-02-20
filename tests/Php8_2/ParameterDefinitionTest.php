<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Php8_2;

use Closure;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionParameter;
use Yiisoft\Definitions\ParameterDefinition;
use Yiisoft\Definitions\Tests\Support\Bike;
use Yiisoft\Definitions\Tests\Support\Chair;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class ParameterDefinitionTest extends TestCase
{
    public function testResolveUnionTypeWithIntersectionType(): void
    {
        $container = new SimpleContainer([
            Chair::class => new Chair(),
        ]);

        $definition = new ParameterDefinition(
            $this->getFirstParameter(fn(Bike|(GearBox&stdClass)|Chair $class) => true),
        );

        $result = $definition->resolve($container);

        $this->assertInstanceOf(Chair::class, $result);
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
