<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Php8\Infrastructure;

use DateTime;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Yiisoft\Definitions\ParameterDefinition;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Infrastructure\DefinitionExtractor;
use Yiisoft\Definitions\Tests\Support\ColorInterface;
use Yiisoft\Definitions\Tests\Support\EngineMarkOne;
use Yiisoft\Definitions\Tests\Support\UnionCar;
use Yiisoft\Definitions\Tests\Support\UnionSelfDependency;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class DefinitionExtractorTest extends TestCase
{
    public function testResolveConstructor(): void
    {
        $container = new SimpleContainer();

        /** @var DefinitionInterface[] $dependencies */
        $dependencies = DefinitionExtractor::fromClassName(DateTime::class);

        $this->assertCount(2, $dependencies);
        $this->assertEquals('now', $dependencies['datetime']->resolve($container));
        $this->assertEquals(null, $dependencies['timezone']->resolve($container));
    }

    public function testResolveCarConstructor(): void
    {
        $container = new SimpleContainer([
            EngineMarkOne::class => new EngineMarkOne(),
        ]);

        $dependencies = DefinitionExtractor::fromClassName(UnionCar::class);

        $this->assertCount(1, $dependencies);
        $this->assertInstanceOf(ParameterDefinition::class, $dependencies['engine']);
        $resolved = $dependencies['engine']->resolve($container);
        $this->assertInstanceOf(EngineMarkOne::class, $resolved);
    }

    public function testUnionScalarTypes(): void
    {
        $definition = DefinitionExtractor::fromFunction(
            new ReflectionFunction(static fn (string|int $a): bool => true),
        )['a'];

        $this->assertInstanceOf(ParameterDefinition::class, $definition);
    }

    public function testFromClassWithUnionSelfDependency(): void
    {
        $definition = DefinitionExtractor::fromClassName(UnionSelfDependency::class)['a'];

        $actualType = implode('|', $definition->getReflection()->getType()->getTypes());
        $this->assertInstanceOf(ParameterDefinition::class, $definition);
        $this->assertSame('self|' . ColorInterface::class, $actualType);
    }
}
