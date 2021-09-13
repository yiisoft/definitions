<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Yiisoft\Definitions\Exception\NotFoundException;

final class NotFoundExceptionTest extends TestCase
{
    public function testGetId(): void
    {
        $exception = new NotFoundException('test');

        $this->assertSame('test', $exception->getId());
    }

    public function testMessage(): void
    {
        $exception = new NotFoundException('test');

        $this->assertSame('No definition or class found or resolvable for test.', $exception->getMessage());
    }

    public function testBuildStack(): void
    {
        $exception = new NotFoundException('test', ['a' => [], 'b' => [], 'test' => []]);

        $this->assertSame(
            'No definition or class found or resolvable for test while building a -> b -> test.',
            $exception->getMessage()
        );
    }
}
