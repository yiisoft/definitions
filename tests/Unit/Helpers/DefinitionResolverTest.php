<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\DefinitionResolver;
use Yiisoft\Definitions\ParameterDefinition;
use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\Tests\Support\Car;
use Yiisoft\Definitions\Tests\Support\EngineMarkOne;
use Yiisoft\Definitions\ValueDefinition;
use Yiisoft\Test\Support\Container\SimpleContainer;

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
        $this->assertSame(42, $definition->resolve(new SimpleContainer()));
    }

    public function testEnsureResolvableDefinition(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches(
            '/^Only references are allowed in constructor arguments, a definition object was provided: (\\\\|)' .
            preg_quote(ValueDefinition::class) .
            '.*/'
        );
        DefinitionResolver::ensureResolvable(new ValueDefinition(7));
    }

    public function testCustomArrayOfDefinitions(): void
    {
        $definitions = [];
        $container = new SimpleContainer([
            'int' => 42,
        ]);

        $engineMarkOne = new EngineMarkOne();
        $definitions['value'] = new ValueDefinition($engineMarkOne);

        $reflection = new ReflectionFunction(static fn (...$a) => true);
        $definitions['parameter'] = new ParameterDefinition($reflection->getParameters()[0]);

        $definitions['reference'] = Reference::to('int');

        $result = DefinitionResolver::resolveArray($container, null, $definitions);

        $this->assertSame(
            [
                'value' => $engineMarkOne,
                'reference' => 42,
            ],
            $result
        );
    }
}
