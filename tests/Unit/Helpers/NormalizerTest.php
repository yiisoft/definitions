<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\CallableDefinition;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\Normalizer;
use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\Tests\Support\CarFactory;
use Yiisoft\Definitions\Tests\Support\ColorPink;
use Yiisoft\Definitions\Tests\Support\GearBox;
use Yiisoft\Definitions\ValueDefinition;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class NormalizerTest extends TestCase
{
    protected function setUp(): void
    {
        Normalizer::clearCache();
    }

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

    public function testSameClass(): void
    {
        /** @var ArrayDefinition $definition */
        $definition = Normalizer::normalize(ColorPink::class, ColorPink::class);

        $this->assertInstanceOf(ArrayDefinition::class, $definition);
        $this->assertSame(ColorPink::class, $definition->getClass());
    }

    public function testCachedClass(): void
    {
        Normalizer::normalize(GearBox::class);

        /** @var ArrayDefinition $definition */
        $definition = Normalizer::normalize(GearBox::class);

        $this->assertInstanceOf(ArrayDefinition::class, $definition);
        $this->assertSame(GearBox::class, $definition->getClass());
    }

    public function testLowercaseClass(): void
    {
        $class = 'lowercaseautoloadeddefinitiontest' . str_replace('.', '', uniqid('', true));
        $autoload = static function (string $autoloadedClass) use ($class): void {
            if ($autoloadedClass === $class) {
                eval('class ' . $class . ' {}');
            }
        };

        spl_autoload_register($autoload);
        try {
            /** @var ArrayDefinition $definition */
            $definition = Normalizer::normalize($class);
        } finally {
            spl_autoload_unregister($autoload);
        }

        $this->assertInstanceOf(ArrayDefinition::class, $definition);
        $this->assertSame($class, $definition->getClass());
    }

    public function testCachedReferenceDoesNotTriggerAutoload(): void
    {
        Normalizer::normalize('engine');

        $autoloadedClasses = [];
        $autoload = static function (string $class) use (&$autoloadedClasses): void {
            $autoloadedClasses[] = $class;
        };

        spl_autoload_register($autoload);
        try {
            $definition = Normalizer::normalize('engine');
        } finally {
            spl_autoload_unregister($autoload);
        }

        $this->assertInstanceOf(Reference::class, $definition);
        $this->assertSame([], $autoloadedClasses);
    }

    public function testCachedReference(): void
    {
        Normalizer::normalize('engine');

        $this->assertInstanceOf(Reference::class, Normalizer::normalize('engine'));
    }

    public function testCachedReferenceWithoutPlainReferenceFastPath(): void
    {
        Normalizer::normalize('engine-with-class', GearBox::class);

        $this->assertInstanceOf(Reference::class, Normalizer::normalize('engine-with-class'));
    }

    public function testArray(): void
    {
        /** @var ArrayDefinition $definition */
        $definition = Normalizer::normalize(
            [
                '__construct()' => [42],
            ],
            GearBox::class,
        );

        $this->assertInstanceOf(ArrayDefinition::class, $definition);
        $this->assertSame(GearBox::class, $definition->getClass());
        $this->assertSame([42], $definition->getConstructorArguments());
        $this->assertSame([], $definition->getMethodsAndProperties());
    }

    public function testStaticCallableArray(): void
    {
        $definition = Normalizer::normalize(CarFactory::create(...));

        $this->assertInstanceOf(CallableDefinition::class, $definition);
    }

    public function testObjectCallableArray(): void
    {
        $definition = Normalizer::normalize([new CarFactory(), 'createWithColor']);

        $this->assertInstanceOf(CallableDefinition::class, $definition);
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

    public function testCachedReadyObject(): void
    {
        $object = new stdClass();
        $definition = Normalizer::normalize($object);

        $this->assertSame($definition, Normalizer::normalize($object));
    }

    public function testInteger(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Invalid definition: 42');
        Normalizer::normalize(42);
    }
}
