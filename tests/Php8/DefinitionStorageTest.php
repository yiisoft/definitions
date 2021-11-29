<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Php8;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Definitions\DefinitionStorage;
use Yiisoft\Definitions\Tests\Support\ColorInterface;
use Yiisoft\Definitions\Tests\Support\ColorPink;
use Yiisoft\Definitions\Tests\Support\UnionCar;
use Yiisoft\Definitions\Tests\Support\UnionSelfDependency;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class DefinitionStorageTest extends TestCase
{
    public function testUnresolvableUnionSelfDependency(): void
    {
        $storage = new DefinitionStorage();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Service ' . UnionSelfDependency::class . ' doesn\'t exist in DefinitionStorage.'
        );
        $storage->get(UnionSelfDependency::class);
    }

    public function testResolvableUnionTypeDependency(): void
    {
        $storage = new DefinitionStorage();

        $definition = $storage->get(UnionCar::class);

        $this->assertSame(UnionCar::class, $definition);
    }

    public function testResolvableFromContainerUnionTypeDependency(): void
    {
        $storage = new DefinitionStorage();

        $container = new SimpleContainer([
            ColorInterface::class => new ColorPink(),
        ]);
        $storage->setDelegateContainer($container);

        $definition = $storage->get(UnionSelfDependency::class);

        $this->assertSame(UnionSelfDependency::class, $definition);
    }
}
