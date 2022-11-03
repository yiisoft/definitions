<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\Tests\Support\Car;
use Yiisoft\Definitions\Tests\Support\EngineInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class ReferenceTest extends TestCase
{
    public function testInvalid(): void
    {
        $this->expectException(InvalidConfigException::class);
        Reference::to(['class' => EngineInterface::class]);
    }

    public function testOptional(): void
    {
        $container = new SimpleContainer();

        $reference = Reference::optional(Car::class);

        $this->assertNull($reference->resolve($container));
    }

    public function testNonExist(): void
    {
        $container = new SimpleContainer();

        $reference = Reference::to(EngineInterface::class);

        $this->expectException(NotFoundExceptionInterface::class);
        $reference->resolve($container);
    }
}
