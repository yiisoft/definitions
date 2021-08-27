<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Definitions\Tests\Support\SimpleDependencyResolver;
use Yiisoft\Definitions\ValueDefinition;

final class ValueDefinitionTest extends TestCase
{
    public function testGetType(): void
    {
        $definition = new ValueDefinition(42, 'integer');

        $this->assertSame('integer', $definition->getType());
    }

    public function testDoNotCloneObjectFromContainer(): void
    {
        $dependencyResolver = new SimpleDependencyResolver();

        $object = new stdClass();

        $definition = new ValueDefinition($object, 'object');
        $value = $definition->resolve($dependencyResolver);

        $this->assertSame($object, $value);
    }
}
