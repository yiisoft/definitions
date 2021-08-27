<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Tests\Objects\EngineInterface;
use Yiisoft\Definitions\Tests\Objects\EngineMarkOne;
use Yiisoft\Definitions\Tests\Support\SimpleDependencyResolver;

final class DynamicReferenceTest extends TestCase
{
    public function testString(): void
    {
        $reference = DynamicReference::to(EngineInterface::class);

        $dependencyResolver = new SimpleDependencyResolver([
            EngineInterface::class => new EngineMarkOne(),
        ]);

        $this->assertInstanceOf(EngineMarkOne::class, $reference->resolve($dependencyResolver));
    }

    public function testClosure(): void
    {
        $reference = DynamicReference::to(
            fn(ContainerInterface $container) => $container->get(EngineInterface::class)
        );

        $dependencyResolver = new SimpleDependencyResolver([
            EngineInterface::class => new EngineMarkOne(),
        ]);

        $this->assertInstanceOf(EngineMarkOne::class, $reference->resolve($dependencyResolver));
    }

    public function testStaticClosure(): void
    {
        $reference = DynamicReference::to(
            static fn(ContainerInterface $container) => $container->get(EngineInterface::class)
        );

        $dependencyResolver = new SimpleDependencyResolver([
            EngineInterface::class => new EngineMarkOne(),
        ]);

        $this->assertInstanceOf(EngineMarkOne::class, $reference->resolve($dependencyResolver));
    }

    public function testCallable(): void
    {
        $reference = DynamicReference::to([self::class, 'callableDefinition']);

        $dependencyResolver = new SimpleDependencyResolver([
            EngineInterface::class => new EngineMarkOne(),
        ]);

        $this->assertInstanceOf(EngineMarkOne::class, $reference->resolve($dependencyResolver));
    }

    public static function callableDefinition(ContainerInterface $container): EngineInterface
    {
        return $container->get(EngineInterface::class);
    }

    public function testFullDefinition(): void
    {
        $reference = DynamicReference::to([
            'class' => EngineMarkOne::class,
        ]);
        $this->assertInstanceOf(EngineMarkOne::class, $reference->resolve(new SimpleDependencyResolver()));
    }

    public function testArrayWithoutClass(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/^Array definition should contain the key "class": /');
        DynamicReference::to([
            '__construct()' => [42],
        ]);
    }

    public function testObjectDefinition(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('DynamicReference don\'t support object as definition.');
        DynamicReference::to(new stdClass());
    }
}
