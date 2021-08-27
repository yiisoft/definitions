<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Php8;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Definitions\ClassDefinition;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Tests\Objects\GearBox;
use Yiisoft\Definitions\Tests\Support\SimpleDependencyResolver;

final class ClassDefinitionTest extends TestCase
{
    public function testResolveRequiredUnionTypeWithIncorrectTypeInContainer(): void
    {
        $class = stdClass::class . '|' . GearBox::class;

        $definition = new ClassDefinition($class, false);

        $dependencyResolver = new SimpleDependencyResolver([
            stdClass::class => 42,
            GearBox::class => 7,
        ]);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Container returned incorrect type "integer" for service "' . $class . '".'
        );
        $definition->resolve($dependencyResolver);
    }

    public function testResolveOptionalUnionTypeWithIncorrectTypeInContainer(): void
    {
        $class = stdClass::class . '|' . GearBox::class;

        $definition = new ClassDefinition($class, true);

        $dependencyResolver = new SimpleDependencyResolver([
            stdClass::class => 42,
            GearBox::class => 7,
        ]);

        $result = $definition->resolve($dependencyResolver);

        $this->assertNull($result);
    }
}
