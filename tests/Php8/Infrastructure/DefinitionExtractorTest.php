<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Php8\Infrastructure;

use DateTime;
use PHPUnit\Framework\TestCase;
use Yiisoft\Definitions\ClassDefinition;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Infrastructure\DefinitionExtractor;
use Yiisoft\Definitions\Tests\Objects\EngineMarkOne;
use Yiisoft\Definitions\Tests\Objects\UnionCar;
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
}
