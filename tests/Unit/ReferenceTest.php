<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit;

use PHPUnit\Framework\TestCase;
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

        $reference = Reference::to(Car::class, true);

        $this->assertNull($reference->resolve($container));
    }
}
