<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Php8_4\AsymmetricVisibility;

use PHPUnit\Framework\TestCase;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\DefinitionValidator;

final class DefinitionValidatorTest extends TestCase
{
    public function testPrivateSet(): void
    {
        $definition = [
            'class' => PublicGet::class,
            '$privateVar' => 'test',
        ];

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: property "Yiisoft\Definitions\Tests\Php8_4\AsymmetricVisibility\PublicGet::$privateVar" must be public and writable.'
        );
        DefinitionValidator::validate($definition);
    }

    public function testProtectedSet(): void
    {
        $definition = [
            'class' => PublicGet::class,
            '$protectedVar' => 'test',
        ];

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Invalid definition: property "Yiisoft\Definitions\Tests\Php8_4\AsymmetricVisibility\PublicGet::$protectedVar" must be public and writable.'
        );
        DefinitionValidator::validate($definition);
    }
}
