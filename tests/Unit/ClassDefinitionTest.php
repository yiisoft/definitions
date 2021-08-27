<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Definitions\ClassDefinition;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Tests\Support\SimpleDependencyResolver;

final class ClassDefinitionTest extends TestCase
{
    public function testGetType(): void
    {
        $definition = new ClassDefinition(stdClass::class, true);

        $this->assertSame(stdClass::class, $definition->getType());
    }

    public function testResolveWithIncorrectTypeInContainer(): void
    {
        $definition = new ClassDefinition(stdClass::class, true);

        $dependencyResolver = new SimpleDependencyResolver([stdClass::class => 42]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Container returned incorrect type "integer" for service "' . stdClass::class . '".'
        );
        $definition->resolve($dependencyResolver);
    }
}
