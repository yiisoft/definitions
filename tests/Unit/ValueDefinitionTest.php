<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Definitions\ValueDefinition;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class ValueDefinitionTest extends TestCase
{
    public function testGetType(): void
    {
        $definition = new ValueDefinition(42, 'integer');

        $this->assertSame('integer', $definition->getType());
    }

    public function testDoNotCloneObjectFromContainer(): void
    {
        $container = new SimpleContainer();

        $object = new stdClass();

        $definition = new ValueDefinition($object, 'object');
        $value = $definition->resolve($container);

        $this->assertSame($object, $value);
    }
}
