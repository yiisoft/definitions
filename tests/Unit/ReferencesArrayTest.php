<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\DynamicReferencesArray;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\ReferencesArray;

final class ReferencesArrayTest extends TestCase
{
    public function testReferencesArray(): void
    {
        $ids = ['key1' => 'first', 'key2' => 'second'];

        $references = ReferencesArray::from($ids);

        $this->assertInstanceOf(Reference::class, $references['key1']);
        $this->assertInstanceOf(Reference::class, $references['key2']);
    }

    public function testReferencesArrayFail(): void
    {
        $ids = ['first', 22];

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Values of an array must be string alias or class name.');
        ReferencesArray::from($ids);
    }

    public function testDynamicReferencesArray(): void
    {
        $definitions = [
            'key1' => 'first',
            'key2' => 'second',
            static fn() => 'thrid',
        ];

        $references = DynamicReferencesArray::from($definitions);

        $this->assertInstanceOf(DynamicReference::class, $references['key1']);
        $this->assertInstanceOf(DynamicReference::class, $references['key2']);
        $this->assertInstanceOf(DynamicReference::class, $references[0]);
    }
}
