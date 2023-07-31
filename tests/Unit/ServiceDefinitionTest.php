<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\ServiceDefinition;
use Yiisoft\Definitions\Tests\Support\Car;
use Yiisoft\Definitions\Tests\Support\ColorInterface;
use Yiisoft\Definitions\Tests\Support\ColorPink;
use Yiisoft\Definitions\Tests\Support\EngineInterface;
use Yiisoft\Definitions\Tests\Support\EngineMarkOne;
use Yiisoft\Definitions\Tests\Support\EngineMarkTwo;
use Yiisoft\Definitions\Tests\Support\Mouse;
use Yiisoft\Definitions\Tests\Support\Phone;
use Yiisoft\Definitions\Tests\Support\Recorder;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class ServiceDefinitionTest extends TestCase
{
    public function testClass(): void
    {
        $container = new SimpleContainer();

        $class = Phone::class;

        $definition = ServiceDefinition::for($class);

        self::assertInstanceOf(Phone::class, $definition->resolve($container));
    }

    public function dataConstructor(): array
    {
        return [
            [null, null, []],
            ['Kiradzu', null, ['Kiradzu']],
            ['Kiradzu', null, ['name' => 'Kiradzu']],
            ['Kiradzu', '2.0', ['Kiradzu', '2.0']],
            ['Kiradzu', '2.0', ['name' => 'Kiradzu', 'version' => '2.0']],
        ];
    }

    /**
     * @dataProvider dataConstructor
     */
    public function testConstructor(?string $name, ?string $version, array $constructorArguments): void
    {
        $container = new SimpleContainer();

        $definition = ServiceDefinition::for(Phone::class)
            ->constructor($constructorArguments);

        /** @var Phone $phone */
        $phone = $definition->resolve($container);

        self::assertSame($name, $phone->getName());
        self::assertSame($version, $phone->getVersion());
    }

    /**
     * @dataProvider dataConstructor
     */
    public function testShortConstructor(?string $name, ?string $version, array $constructorArguments): void
    {
        $container = new SimpleContainer();

        $definition = ServiceDefinition::for(Phone::class, $constructorArguments);

        /** @var Phone $phone */
        $phone = $definition->resolve($container);

        self::assertSame($name, $phone->getName());
        self::assertSame($version, $phone->getVersion());
    }

    public function testConstructorWithVariadicAndIntKeys(): void
    {
        $container = new SimpleContainer();

        $colors = ['red', 'green', 'blue'];

        $definition = ServiceDefinition::for(Phone::class)
            ->constructor([
                null,
                null,
                $colors[0],
                $colors[1],
                $colors[2],
            ]);

        /** @var Phone $phone */
        $phone = $definition->resolve($container);

        self::assertSame($colors, $phone->getColors());
    }

    public function testConstructorWithVariadicArrayAndIntKeys(): void
    {
        $container = new SimpleContainer();

        $colors = ['red', 'green', 'blue'];

        $definition = ServiceDefinition::for(Phone::class)
            ->constructor([
                null,
                null,
                $colors,
            ]);

        /** @var Phone $phone */
        $phone = $definition->resolve($container);


        self::assertSame([$colors], $phone->getColors());
    }

    public function testConstructorWithVariadicAndNamedKeys(): void
    {
        $container = new SimpleContainer();

        $colors = ['red', 'green', 'blue'];
        $definition = ServiceDefinition::for(Phone::class)
            ->constructor([
                'name' => null,
                'version' => null,
                'colors' => $colors,
            ]);

        /** @var Phone $phone */
        $phone = $definition->resolve($container);

        self::assertSame($colors, $phone->getColors());
    }

    public function testConstructorWithWrongVariadicArgument(): void
    {
        $container = new SimpleContainer();

        $colors = 'red';
        $definition = ServiceDefinition::for(Phone::class)
            ->constructor([
                'name' => null,
                'version' => null,
                'colors' => $colors,
            ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Named argument for a variadic parameter should be an array, "string" given.');

        $definition->resolve($container);
    }

    public function dataSetProperties(): array
    {
        return [
            [false, null, []],
            [true, null, ['dev' => true]],
            [true, 'Radar', ['dev' => true, 'codeName' => 'Radar']],
        ];
    }

    /**
     * @dataProvider dataSetProperties
     */
    public function testSetProperties(bool $dev, ?string $codeName, array $setProperties): void
    {
        $container = new SimpleContainer();

        $definition = ServiceDefinition::for(Phone::class)
            ->sets($setProperties);

        /** @var Phone $phone */
        $phone = $definition->resolve($container);

        self::assertSame($dev, $phone->dev);
        self::assertSame($codeName, $phone->codeName);
    }

    public function dataCallMethods(): array
    {
        return [
            [null, [], []],
            ['s43g23456', [], ['setId' => ['s43g23456']]],
            ['777', [], ['setId777' => []]],
            [
                '777',
                [['Browser', null]],
                [
                    'addApp' => ['Browser'],
                    'setId777' => [],
                ],
            ],
            [
                '42',
                [['Browser', '7']],
                [
                    'setId' => ['42'],
                    'addApp' => ['Browser', '7'],
                ],
            ],
            [
                null,
                [['Browser', '7']],
                [
                    'addApp' => ['name' => 'Browser', 'version' => '7'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataCallMethods
     */
    public function testCallMethods(?string $id, array $apps, array $callMethods): void
    {
        $container = new SimpleContainer();

        $definition = ServiceDefinition::for(Phone::class)
            ->calls($callMethods);

        /** @var Phone $phone */
        $phone = $definition->resolve($container);

        self::assertSame($id, $phone->getId());
        self::assertSame($apps, $phone->getApps());
    }

    public function testCallFluentMethod(): void
    {
        $container = new SimpleContainer();

        $author = 'Sergei';
        $country = 'Russia';
        $definition = ServiceDefinition::for(Phone::class)
            ->call('withAuthor', [$author])
            ->call('withCountry', [$country]);

        /** @var Phone $phone */
        $phone = $definition->resolve($container);

        self::assertSame($author, $phone->getAuthor());
        self::assertSame($country, $phone->getCountry());
    }

    public function dataMethodAutowiring(): array
    {
        return [
            [
                'kitty',
                EngineMarkOne::class,
                ['kitty'],
            ],
            [
                'kitty',
                EngineMarkOne::class,
                ['name' => 'kitty'],
            ],
            [
                'kitty',
                EngineMarkTwo::class,
                ['kitty', new EngineMarkTwo()],
            ],
            [
                'kitty',
                EngineMarkTwo::class,
                ['name' => 'kitty', 'engine' => new EngineMarkTwo()],
            ],
            [
                'kitty',
                EngineMarkTwo::class,
                ['kitty', Reference::to('mark2')],
            ],
        ];
    }

    /**
     * @dataProvider dataMethodAutowiring
     */
    public function testMethodAutowiring(?string $expectedName, ?string $expectedEngine, array $data): void
    {
        $container = new SimpleContainer([
            EngineInterface::class => new EngineMarkOne(),
            'mark2' => new EngineMarkTwo(),
        ]);

        $definition = ServiceDefinition::for(Mouse::class)
            ->call('setNameAndEngine', $data);

        /** @var Mouse $mouse */
        $mouse = $definition->resolve($container);

        self::assertSame($expectedName, $mouse->getName());
        self::assertInstanceOf($expectedEngine, $mouse->getEngine());
    }

    public function dataMethodVariadic(): array
    {
        return [
            [
                'kitty',
                [],
                ['kitty'],
            ],
            [
                'kitty',
                [],
                ['name' => 'kitty'],
            ],
            [
                'kitty',
                [],
                ['name' => 'kitty', 'colors' => []],
            ],
            [
                'kitty',
                [1, 2, 3],
                ['name' => 'kitty', 'colors' => [1, 2, 3]],
            ],
            [
                'kitty',
                [1, 2, 3],
                ['kitty', 1, 2, 3],
            ],
            [
                'kitty',
                [[1, 2, 3]],
                ['kitty', [1, 2, 3]],
            ],
            [
                'kitty',
                [1, 2, 3],
                ['name' => 'kitty', 'colors' => Reference::to('data')],
                ['data' => [1, 2, 3]],
            ],
            [
                'kitty',
                [[1, 2, 3]],
                ['kitty', Reference::to('data')],
                ['data' => [1, 2, 3]],
            ],
        ];
    }

    /**
     * @dataProvider dataMethodVariadic
     */
    public function testMethodVariadic(
        ?string $expectedName,
        array $expectedColors,
        array $data,
        array $containerDefinitions = []
    ): void {
        $container = new SimpleContainer($containerDefinitions);

        $definition = ServiceDefinition::for(Mouse::class)
            ->call('setNameAndColors', $data);

        /** @var Mouse $mouse */
        $mouse = $definition->resolve($container);

        self::assertSame($expectedName, $mouse->getName());
        self::assertSame($expectedColors, $mouse->getColors());
    }

    public function testArgumentsIndexedBothByNameAndByPositionInMethod(): void
    {
        $definition = ServiceDefinition::for(Mouse::class)
            ->call('setNameAndEngine', ['kitty', 'engine' => new EngineMarkOne()]);

        $container = new SimpleContainer();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Arguments indexed both by name and by position are not allowed in the same array.'
        );
        $definition->resolve($container);
    }

    public function testMethodWithWrongVariadicArgument(): void
    {
        $container = new SimpleContainer();

        $definition = ServiceDefinition::for(Mouse::class)
            ->call('setNameAndColors', [
                'name' => 'kitty',
                'colors' => 'red',
            ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Named argument for a variadic parameter should be an array, "string" given.');
        $definition->resolve($container);
    }

    public function testMethodWithWrongReferenceVariadicArgument(): void
    {
        $container = new SimpleContainer([
            'data' => 32,
        ]);

        $definition = ServiceDefinition::for(Mouse::class)
            ->call('setNameAndColors', [
                'name' => 'kitty',
                'colors' => Reference::to('data'),
            ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Named argument for a variadic parameter should be an array, "integer" given.');
        $definition->resolve($container);
    }

    public function testMerge(): void
    {
        $this->markTestSkipped('TBD');

        $a = ServiceDefinition::for(Phone::class)
            ->constructor(['version' => '2.0'])
            ->set('codeName', 'a')
            ->call('setColors', ['red', 'green'], );

        $b = ServiceDefinition::for(Phone::class)
            ->constructor(['name' => 'Retro', 'version' => '1.0'])
            ->set('dev', true)
            ->set('codeName', 'b')
            ->call('setId', [42])
            ->call('setColors', ['yellow']);

        $c = $a->merge($b);

        $this->assertSame(Phone::class, $c->getClass());
        $this->assertSame(['name' => 'Retro', 'version' => '2.0'], $c->getConstructorArguments());
        $this->assertSame(
            [
                'codeName' => [ArrayDefinition::TYPE_PROPERTY, 'codeName', 'b'],
                'setColors' => [ArrayDefinition::TYPE_METHOD, 'setColors', ['yellow', 'green']],
                'dev' => [ArrayDefinition::TYPE_PROPERTY, 'dev', true],
                'setId' =>[ArrayDefinition::TYPE_METHOD, 'setId', [42]],
            ],
            $c->getMethodsAndProperties(),
        );
    }

    public function testMergeImmutability(): void
    {
        $this->markTestSkipped('TBD');
        $a = ServiceDefinition::for(Phone::class);
        $b = ServiceDefinition::for(Phone::class);
        $c = $a->merge($b);
        $this->assertNotSame($a, $c);
        $this->assertNotSame($b, $c);
    }

    public function testArgumentsIndexedBothByNameAndByPosition(): void
    {
        $definition = ServiceDefinition::for(Phone::class)
            ->constructor(['name' => 'Taruto', '1.0']);
        $container = new SimpleContainer();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Arguments indexed both by name and by position are not allowed in the same array.'
        );
        $definition->resolve($container);
    }

    public function testReferenceContainer(): void
    {
        $this->markTestSkipped('TBD');
        $container = new SimpleContainer([
            EngineInterface::class => new EngineMarkOne(),
        ]);
        $referenceContainer = new SimpleContainer([
            ColorInterface::class => new ColorPink(),
            EngineInterface::class => new EngineMarkTwo(),
        ]);

        $definition = ServiceDefinition::for(Car::class)
            ->call('setColor', [Reference::to(ColorInterface::class)]);

        $newDefinition = $definition->withReferenceContainer($referenceContainer);

        /** @var Car $object */
        $object = $newDefinition->resolve($container);

        $this->assertNotSame($definition, $newDefinition);
        $this->assertInstanceOf(Car::class, $object);
        $this->assertInstanceOf(EngineMarkOne::class, $object->getEngine());
    }

    public function testMagicMethods(): void
    {
        $definiton = ServiceDefinition::for(Recorder::class)
            ->call('first')
            ->set('second', null)
            ->call('third', ['hello', true])
            ->set('fourth', 'hello');

        $object = $definiton->resolve(new SimpleContainer());

        $this->assertSame(
            [
                'Call first()',
                'Set $second to null',
                'Call third(string, bool)',
                'Set $fourth to string',
            ],
            $object->getEvents()
        );
    }
}
