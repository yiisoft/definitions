<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Php8_1\Helpers;

use PHPUnit\Framework\TestCase;
use Yiisoft\Definitions\Helpers\DefinitionExtractor;
use Yiisoft\Definitions\Tests\Support\Chair;
use Yiisoft\Definitions\Tests\Support\ResolvableDependencyWithDefaultObject;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class DefinitionExtractorTest extends TestCase
{
    public function testResolvableDependencyWithDefaultObject(): void
    {
        $container = new SimpleContainer([
            Chair::class => new Chair(),
        ]);

        $definitions = DefinitionExtractor::fromClassName(ResolvableDependencyWithDefaultObject::class);

        $this->assertInstanceOf(Chair::class, $definitions['chair']->resolve($container));
    }
}
