<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProxyManager\Exception\InvalidProxiedClassException;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\LazyDefinition;
use Yiisoft\Definitions\Tests\Support\EngineInterface;
use Yiisoft\Definitions\Tests\Support\NotFinalClass;
use Yiisoft\Definitions\Tests\Support\Phone;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class LazyDefinitionTest extends TestCase
{
    public function testFinalClass(): void
    {
        $container = new SimpleContainer([
            LazyLoadingValueHolderFactory::class => new LazyLoadingValueHolderFactory(),
        ]);

        $class = Phone::class;

        $definition = new LazyDefinition([ArrayDefinition::CLASS_NAME => $class], $class);

        $this->expectException(InvalidProxiedClassException::class);
        $definition->resolve($container);
    }

    public function testNotFinalClass(): void
    {
        $container = new SimpleContainer([
            LazyLoadingValueHolderFactory::class => new LazyLoadingValueHolderFactory(),
        ]);

        $class = NotFinalClass::class;

        $definition = new LazyDefinition([ArrayDefinition::CLASS_NAME => $class], $class);

        $object = $definition->resolve($container);

        self::assertInstanceOf($class, $object);
        self::assertInstanceOf(LazyLoadingInterface::class, $object);
    }

    public function testInterface(): void
    {
        $container = new SimpleContainer([
            LazyLoadingValueHolderFactory::class => new LazyLoadingValueHolderFactory(),
        ]);

        $class = EngineInterface::class;

        $definition = new LazyDefinition([ArrayDefinition::CLASS_NAME => $class], $class);

        $engine = $definition->resolve($container);

        self::assertInstanceOf($class, $engine);
        self::assertInstanceOf(LazyLoadingInterface::class, $engine);
    }
}
