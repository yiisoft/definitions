<?php

declare(strict_types=1);

namespace Helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\ArrayDefinitionHelper;

final class ArrayDefinitionHelperTest extends TestCase
{
    public static function dataBase(): array
    {
        return [
            'without configs' => [
                [],
                [],
            ],
            'one config' => [
                ['$value' => 42],
                [
                    ['$value' => 42],
                ],
            ],
            'indexed constructor arguments' => [
                ['__construct()' => ['y', 42]],
                [
                    ['__construct()' => ['x']],
                    ['__construct()' => ['y', 42]],
                ],
            ],
            'named constructor arguments' => [
                ['__construct()' => ['number' => 42, 'color' => 'red']],
                [
                    ['__construct()' => ['number' => 42]],
                    ['__construct()' => ['color' => 'green']],
                    ['__construct()' => ['color' => 'red']],
                ],
            ],
            'indexed method arguments' => [
                ['run()' => ['y', 42]],
                [
                    ['run()' => ['x']],
                    ['run()' => ['y', 42]],
                ],
            ],
            'named method arguments' => [
                ['run()' => ['number' => 42, 'color' => 'red']],
                [
                    ['run()' => ['number' => 42]],
                    ['run()' => ['color' => 'green']],
                    ['run()' => ['color' => 'red']],
                ],
            ],
            'extra keys' => [
                ['$value' => 7, 'meta' => [1, 2]],
                [
                    ['$value' => 7, 'meta' => 42],
                    ['meta' => [1, 2]],
                ],
            ],
            'complex test' => [
                [
                    '__construct()' => ['number' => 42, 'color' => 'green'],
                    'run()' => [15, 23],
                    '$value' => 7,
                    '$count' => 7,
                    'support' => ['name' => 'background'],
                    'do()' => [1],
                ],
                [
                    [
                        '__construct()' => ['number' => 42, 'color' => 'red'],
                        'run()' => [3],
                    ],
                    [
                        '$value' => 7,
                        'run()' => [15, 23],
                        '$count' => 7,
                        'support' => ['name' => 'background'],
                    ],
                    [
                        '__construct()' => ['color' => 'green'],
                        'do()' => [1],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('dataBase')]
    public function testBase(array $expected, array $configs): void
    {
        $result = ArrayDefinitionHelper::merge(...$configs);
        $this->assertSame($expected, $result);
    }

    public static function dataInvalidConfigException(): array
    {
        return [
            'non-string key' => [
                'Invalid definition: invalid key in array definition. Only string keys are allowed, got 0.',
                [
                    [],
                    ['run'],
                ],
            ],
            'non-array constructor arguments 1' => [
                'Invalid definition: incorrect constructor arguments. Expected array, got string.',
                [
                    ['__construct()' => ['finish']],
                    ['__construct()' => 'start'],
                ],
            ],
            'non-array constructor arguments 2' => [
                'Invalid definition: incorrect constructor arguments. Expected array, got string.',
                [
                    ['__construct()' => 'start'],
                    ['__construct()' => ['finish']],
                ],
            ],
            'non-array method arguments 1' => [
                'Invalid definition: incorrect method "run()" arguments. Expected array, got "string". Probably you should wrap them into square brackets.',
                [
                    ['run()' => ['finish']],
                    ['run()' => 'start'],
                ],
            ],
            'non-array method arguments 2' => [
                'Invalid definition: incorrect method "run()" arguments. Expected array, got "string". Probably you should wrap them into square brackets.',
                [
                    ['run()' => 'start'],
                    ['run()' => ['finish']],
                ],
            ],
        ];
    }

    #[DataProvider('dataInvalidConfigException')]
    public function testInvalidConfigException(string $expectedMessage, array $configs): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($expectedMessage);
        ArrayDefinitionHelper::merge(...$configs);
    }
}
