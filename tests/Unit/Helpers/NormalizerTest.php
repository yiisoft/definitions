<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\Normalizer;
use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\Tests\Support\ColorPink;
use Yiisoft\Definitions\Tests\Support\GearBox;
use Yiisoft\Definitions\ValueDefinition;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class NormalizerTest extends TestCase
{
    public function testReference(): void
    {
        $reference = Reference::to('test');

        $this->assertSame($reference, Normalizer::normalize($reference));
    }

    public function testClass(): void
    {
        /** @var ArrayDefinition $definition */
        $definition = Normalizer::normalize(ColorPink::class);

        $this->assertInstanceOf(ArrayDefinition::class, $definition);
        $this->assertSame(ColorPink::class, $definition->getClass());
        $this->assertSame([], $definition->getConstructorArguments());
        $this->assertSame([], $definition->getMethodsAndProperties());
    }

    public function testArray(): void
    {
        /** @var ArrayDefinition $definition */
        $definition = Normalizer::normalize(
            [
                '__construct()' => [42],
            ],
            GearBox::class
        );

        $this->assertInstanceOf(ArrayDefinition::class, $definition);
        $this->assertSame(GearBox::class, $definition->getClass());
        $this->assertSame([42], $definition->getConstructorArguments());
        $this->assertSame([], $definition->getMethodsAndProperties());
    }

    public function testReadyObject(): void
    {
        $container = new SimpleContainer();

        $object = new stdClass();

        /** @var ValueDefinition $definition */
        $definition = Normalizer::normalize($object);

        $this->assertInstanceOf(ValueDefinition::class, $definition);
        $this->assertSame($object, $definition->resolve($container));
    }

    public function testInteger(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Invalid definition: 42');
        Normalizer::normalize(42);
    }
}
