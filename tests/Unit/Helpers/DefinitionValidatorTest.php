<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\DefinitionValidator;
use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\Tests\Support\Car;
use Yiisoft\Definitions\Tests\Support\CarFactory;
use Yiisoft\Definitions\Tests\Support\ColorPink;
use Yiisoft\Definitions\Tests\Support\GearBox;
use Yiisoft\Definitions\Tests\Support\Phone;
use Yiisoft\Definitions\ValueDefinition;

final class DefinitionValidatorTest extends TestCase
{
    public function testIntegerKeyOfArray(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Invalid definition: invalid key in array definition. Allow only string keys, got 0.');
        DefinitionValidator::validate([
            ArrayDefinition::CLASS_NAME => Phone::class,
            'RX',
        ]);
    }

    public function dataInvalidClass(): array
    {
        return [
            [42, 'Invalid definition: class name must be a non-empty string, got int.'],
            ['', 'Invalid definition: class name must be a non-empty string, got "".'],
        ];
    }

    /**
     * @dataProvider dataInvalidClass
     */
    public function testInvalidClass($class, string $message): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);
        DefinitionValidator::validate([
            ArrayDefinition::CLASS_NAME => $class,
        ]);
    }

    public function dataInvalidProperty(): array
    {
        $object1 = new class () {
            public bool $visible = true;
            private bool $invisible = true;
        };
        return [
            [new stdClass(), '$', 'Invalid definition: class "stdClass" does not have any public properties.'],
            [
                $object1,
                '$1',
                sprintf(
                    'Invalid definition: class "%s" does not have the public property with name "1". Possible properties to set: "visible".',
                    $object1::class
                ),
            ],[
                $object1,
                '$invisible',
                sprintf(
                    'Invalid definition: property "%s" must be public.',
                    $object1::class . '::$invisible',
                ),
            ],
        ];
    }

    /**
     * @dataProvider dataInvalidProperty
     */
    public function testInvalidProperty($object, $property, string $message): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);
        DefinitionValidator::validate([
            ArrayDefinition::CLASS_NAME => $object::class,
            $property => [],
        ]);
    }

    public function testWithoutClass(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Invalid definition: no class name specified.');
        DefinitionValidator::validate([]);
    }

    public function dataInvalidStringDefinition(): array
    {
        return [
            ['', 'Invalid definition: class name must be a non-empty string, got "".'],
            ['not a class', 'Invalid definition: class "not a class" does not exist.'],
        ];
    }

    /**
     * @dataProvider dataInvalidStringDefinition
     */
    public function testInvalidStringDefinition(string $definition, string $message): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);
        DefinitionValidator::validate($definition);
    }

    public function testInvalidConstructor(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Invalid definition: incorrect constructor arguments. Expected array, got string.');
        DefinitionValidator::validate([
            ArrayDefinition::CLASS_NAME => Phone::class,
            ArrayDefinition::CONSTRUCTOR => 'Kiradzu',
        ]);
    }

    public function dataInvalidMethodCalls(): array
    {
        return [
            [Phone::class,['addApp()' => 'Browser'], 'Invalid definition: incorrect method "addApp()" arguments. Expected array, got "string". Probably you should wrap them into square brackets.'],
            [Phone::class,['deleteApp()' => ['Browser']], sprintf(
                'Invalid definition: class "%s" does not have the public method with name "deleteApp". Possible methods to call: "__construct", "getName", "getVersion", "getColors", "addApp", "getApps", "getId", "setId", "setId777", "withAuthor", "getAuthor", "withCountry", "getCountry"',
                Phone::class,
            )],
            [stdClass::class,['addApp()' => ['Browser']], 'Invalid definition: class "stdClass" does not have the public method with name "addApp". No public methods available to call.'],
        ];
    }

    /**
     * @dataProvider dataInvalidMethodCalls
     */
    public function testInvalidMethodCalls(string $class, array $methodCalls, string $message): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($message);
        DefinitionValidator::validate(array_merge([
            ArrayDefinition::CLASS_NAME => $class,
        ], $methodCalls));
    }

    public function dataErrorOnPropertyOrMethodTypo(): array
    {
        return [
            ['dev', true, 'Invalid definition: key "dev" is not allowed. Did you mean "$dev"?'],
            ['setId', [42], 'Invalid definition: key "setId" is not allowed. Did you mean "setId()"?'],
            [
                'getCountryPrivate',
                [42],
                sprintf(
                    'Invalid definition: key "getCountryPrivate" is not allowed. Method "%s" must be public to be able to be called.',
                    Phone::class . '::getCountryPrivate()',
                ),
            ],[
                'country',
                [42],
                sprintf(
                    'Invalid definition: key "country" is not allowed. Property "%s" must be public to be able to be called.',
                    Phone::class . '::$country',
                ),
            ],
            [
                '()test',
                [42],
                'Invalid definition: key "()test" is not allowed. The key may be a call of a method or a setting of a property.',
            ],
            [
                'var$',
                true,
                'Invalid definition: key "var$" is not allowed. The key may be a call of a method or a setting of a property.',
            ],
            [
                ' var$',
                true,
                'Invalid definition: key " var$" is not allowed. The key may be a call of a method or a setting of a property.',
            ],
            [
                '100$',
                true,
                'Invalid definition: key "100$" is not allowed. The key may be a call of a method or a setting of a property.',
            ],
            [
                'test-тест',
                true,
                'Invalid definition: key "test-тест" is not allowed. The key may be a call of a method or a setting of a property.',
            ],
        ];
    }

    /**
     * @dataProvider dataErrorOnPropertyOrMethodTypo
     */
    public function testErrorOnPropertyOrMethodTypo(string $key, $value, string $errorMessage): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage($errorMessage);
        DefinitionValidator::validate([
            'class' => Phone::class,
            '__construct()' => ['name' => 'hello'],
            '$dev' => true,
            'setId()' => [24],
            $key => $value,
        ]);
    }

    public function dataValidate(): array
    {
        return [
            'ready-object' => [new stdClass()],
            'reference' => [Reference::to('test')],
            'class-name' => [Car::class],
            'callable' => [[CarFactory::class, 'create']],
            'array-definition' => [['class' => ColorPink::class]],
        ];
    }

    /**
     * @dataProvider dataValidate
     */
    public function testValidate($definition, ?string $id = null): void
    {
        DefinitionValidator::validate($definition, $id);
        $this->assertSame(1, 1);
    }

    public function dataValidateArrayDefinition(): array
    {
        return [
            [['class' => ColorPink::class]],
            [[], ColorPink::class],
        ];
    }

    /**
     * @dataProvider dataValidateArrayDefinition
     */
    public function testValidateArrayDefinition(array $definition, ?string $id = null): void
    {
        DefinitionValidator::validateArrayDefinition($definition, $id);
        $this->assertSame(1, 1);
    }

    public function testInteger(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Invalid definition: 42');
        DefinitionValidator::validate(42);
    }

    public function testDefinitionInArguments(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(
            'Only references are allowed in constructor arguments, a definition object was provided: ' .
            ValueDefinition::class
        );
        DefinitionValidator::validate([
            'class' => GearBox::class,
            '__construct()' => [
                new ValueDefinition(56),
            ],
        ]);
    }

    public function testIncorrectMethodName(): void
    {
        $config = [
            'class' => Phone::class,
            'addApp()hm()' => ['name' => 'hello'],
        ];

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid definition: class "%s" does not have the public method with name "addApp()hm".',
            Phone::class,
        ));
        DefinitionValidator::validate($config);
    }
}
