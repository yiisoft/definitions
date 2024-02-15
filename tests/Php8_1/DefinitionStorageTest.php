<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Php8_1;

use PHPUnit\Framework\TestCase;
use Yiisoft\Definitions\DefinitionStorage;
use Yiisoft\Definitions\Tests\Support\UnionTypeWithIntersectionTypeDependency;

final class DefinitionStorageTest extends TestCase
{
    public function testResolvableUnionTypeWithIntersectionTypeDependency(): void
    {
        $storage = new DefinitionStorage();

        $definition = $storage->get(UnionTypeWithIntersectionTypeDependency::class);

        $this->assertSame(UnionTypeWithIntersectionTypeDependency::class, $definition);
    }
}
