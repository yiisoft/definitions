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
use Yiisoft\Test\Support\Container\SimpleContainer;

final class DynamicReferenceTest extends TestCase
{
    public function testString(): void
    {
        $reference = DynamicReference::to(EngineInterface::class);

        $container = new SimpleContainer([
            EngineInterface::class => new EngineMarkOne(),
        ]);

        $this->assertInstanceOf(EngineMarkOne::class, $reference->resolve($container));
    }

    public function testClosure(): void
    {
        $reference = DynamicReference::to(
            fn (ContainerInterface $container) => $container->get(EngineInterface::class)
        );

        $container = new SimpleContainer([
            ContainerInterface::class => &$container,
            EngineInterface::class => new EngineMarkOne(),
        ]);

        $this->assertInstanceOf(EngineMarkOne::class, $reference->resolve($container));
    }

    public function testStaticClosure(): void
    {
        $reference = DynamicReference::to(
            static fn (ContainerInterface $container) => $container->get(EngineInterface::class)
        );

        $container = new SimpleContainer([
            ContainerInterface::class => &$container,
            EngineInterface::class => new EngineMarkOne(),
        ]);

        $this->assertInstanceOf(EngineMarkOne::class, $reference->resolve($container));
    }

    public function testCallable(): void
    {
        $reference = DynamicReference::to([self::class, 'callableDefinition']);

        $container = new SimpleContainer([
            ContainerInterface::class => &$container,
            EngineInterface::class => new EngineMarkOne(),
        ]);

        $this->assertInstanceOf(EngineMarkOne::class, $reference->resolve($container));
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
        $this->assertInstanceOf(EngineMarkOne::class, $reference->resolve(new SimpleContainer()));
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
