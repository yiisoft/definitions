<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit\Infrastructure;

use PHPUnit\Framework\TestCase;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Infrastructure\DefinitionResolver;
use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\Tests\Objects\Car;
use Yiisoft\Definitions\Tests\Support\SimpleDependencyResolver;
use Yiisoft\Definitions\ValueDefinition;

final class DefinitionResolverTest extends TestCase
{
    public function testEnsureResolvableReference(): void
    {
        $reference = Reference::to('test');

        $this->assertSame($reference, DefinitionResolver::ensureResolvable($reference));
    }

    public function testEnsureResolvableArray(): void
    {
        $array = ['class' => Car::class];

        $this->assertSame($array, DefinitionResolver::ensureResolvable($array));
    }

    public function testEnsureResolvableScalar(): void
    {
        /** @var ValueDefinition $definition */
        $definition = DefinitionResolver::ensureResolvable(42);

        $this->assertInstanceOf(ValueDefinition::class, $definition);
        $this->assertSame(42, $definition->resolve(new SimpleDependencyResolver()));
    }

    public function testEnsureResolvableDefinition(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Only references are allowed in constructor arguments, a definition object was provided: ' .
            ValueDefinition::class
        );
        DefinitionResolver::ensureResolvable(new ValueDefinition(7));
    }
}
