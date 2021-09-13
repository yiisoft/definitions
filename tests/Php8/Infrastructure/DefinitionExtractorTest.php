<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Php8\Infrastructure;

use DateTime;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Yiisoft\Definitions\ClassDefinition;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Infrastructure\DefinitionExtractor;
use Yiisoft\Definitions\ParameterDefinition;
use Yiisoft\Definitions\Tests\Objects\ColorInterface;
use Yiisoft\Definitions\Tests\Objects\EngineMarkOne;
use Yiisoft\Definitions\Tests\Objects\UnionCar;
use Yiisoft\Definitions\Tests\Objects\UnionSelfDependency;
use Yiisoft\Definitions\Tests\Support\SimpleDependencyResolver;

final class DefinitionExtractorTest extends TestCase
{
    public function testResolveConstructor(): void
    {
        $extractor = DefinitionExtractor::getInstance();
        $dependencyResolver = new SimpleDependencyResolver();

        /** @var DefinitionInterface[] $dependencies
         */
        $dependencies = $extractor->fromClassName(DateTime::class);

        $this->assertCount(2, $dependencies);
        $this->assertEquals('now', $dependencies['datetime']->resolve($dependencyResolver));
        $this->assertEquals(null, $dependencies['timezone']->resolve($dependencyResolver));
    }

    public function testResolveCarConstructor(): void
    {
        $extractor = DefinitionExtractor::getInstance();
        $dependencyResolver = new SimpleDependencyResolver([
            EngineMarkOne::class => new EngineMarkOne(),
        ]);

        $dependencies = $extractor->fromClassName(UnionCar::class);

        $this->assertCount(1, $dependencies);
        $this->assertInstanceOf(ClassDefinition::class, $dependencies['engine']);
        $resolved = $dependencies['engine']->resolve($dependencyResolver);
        $this->assertInstanceOf(EngineMarkOne::class, $resolved);
    }

    public function testUnionScalarTypes(): void
    {
        $extractor = DefinitionExtractor::getInstance();

        $definition = $extractor->fromFunction(
            new ReflectionFunction(static fn (string|int $a): bool => true),
        )['a'];

        $this->assertInstanceOf(ParameterDefinition::class, $definition);
    }

    public function testFromClassWithUnionSelfDependency(): void
    {
        /** @var ClassDefinition $definition */
        $definition = DefinitionExtractor::getInstance()->fromClassName(UnionSelfDependency::class)['a'];

        $this->assertInstanceOf(ClassDefinition::class, $definition);
        $this->assertSame(UnionSelfDependency::class . '|' . ColorInterface::class, $definition->getType());
    }
}
