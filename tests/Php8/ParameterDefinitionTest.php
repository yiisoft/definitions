<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Php8;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\ParameterDefinition;
use Yiisoft\Definitions\Tests\Objects\EngineMarkOne;
use Yiisoft\Definitions\Tests\Objects\EngineMarkTwo;
use Yiisoft\Definitions\Tests\Objects\UnionCar;
use Yiisoft\Definitions\Tests\Support\SimpleDependencyResolver;

final class ParameterDefinitionTest extends TestCase
{
    public function testNotInstantiable(): void
    {
        $definition = new ParameterDefinition(
            (new ReflectionClass(UnionCar::class))->getConstructor()->getParameters()[0]
        );
        $dependencyResolver = new SimpleDependencyResolver();

        $this->expectException(NotInstantiableException::class);
        $this->expectExceptionMessage(
            'Can not determine value of the "engine" parameter of type "' .
            'Yiisoft\Definitions\Tests\Objects\NonExistingEngine|' . EngineMarkOne::class . '|' . EngineMarkTwo::class .
            '" when instantiating "' .
            UnionCar::class . '::__construct()"' .
            '. Please specify argument explicitly.'
        );
        $definition->resolve($dependencyResolver);
    }
}
