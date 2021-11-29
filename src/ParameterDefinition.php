<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Psr\Container\ContainerInterface;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Throwable;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use function get_class;
use function gettype;
use function is_object;

/**
 * Parameter definition resolves an object based on information from `ReflectionParameter` instance.
 */
final class ParameterDefinition implements DefinitionInterface
{
    private ReflectionParameter $parameter;

    public function __construct(ReflectionParameter $parameter)
    {
        $this->parameter = $parameter;
    }

    public function getReflection(): ReflectionParameter
    {
        return $this->parameter;
    }

    public function isVariadic(): bool
    {
        return $this->parameter->isVariadic();
    }

    public function isOptional(): bool
    {
        return $this->parameter->isOptional();
    }

    public function isBuiltin(): bool
    {
        $type = $this->parameter->getType();
        if ($type === null) {
            return false;
        }
        return $type->isBuiltin();
    }

    public function hasValue(): bool
    {
        return $this->parameter->isDefaultValueAvailable();
    }

    public function resolve(ContainerInterface $container)
    {
        $type = $this->parameter->getType();

        if ($type === null || $this->isVariadic()) {
            return $this->resolveVariadicOrBuiltinOrNonTyped();
        }

        if ($this->isUnionType()) {
            return $this->resolveUnionType($container);
        }

        if (!$this->isBuiltin()) {
            /** @var ReflectionNamedType $type */
            $typeName = $type->getName();
            if ($typeName === 'self') {
                // If type name is "self", it means that called class and
                // $parameter->getDeclaringClass() returned instance of `ReflectionClass`.
                /** @psalm-suppress PossiblyNullReference */
                $typeName = $this->parameter->getDeclaringClass()->getName();
            }

            try {
                /** @var mixed */
                $result = $container->get($typeName);
            } catch (Throwable $t) {
                if ($this->parameter->isOptional()) {
                    return null;
                }
                throw $t;
            }

            if (!$result instanceof $typeName) {
                $actualType = $this->getValueType($result);
                throw new InvalidConfigException(
                    "Container returned incorrect type \"$actualType\" for service \"{$type->getName()}\"."
                );
            }
            return $result;
        }

        return $this->resolveVariadicOrBuiltinOrNonTyped();
    }

    /**
     * @return mixed
     */
    private function resolveVariadicOrBuiltinOrNonTyped()
    {
        if ($this->parameter->isDefaultValueAvailable()) {
            return $this->parameter->getDefaultValue();
        }

        if ($this->isOptional()) {
            throw new NotInstantiableException(
                sprintf(
                    'Can not determine default value of parameter "%s" when instantiating "%s" ' .
                    'because it is PHP internal. Please specify argument explicitly.',
                    $this->parameter->getName(),
                    $this->getCallable(),
                )
            );
        }

        $type = $this->getType();

        if ($type === null) {
            throw new NotInstantiableException(
                sprintf(
                    'Can not determine value of the "%s" parameter without type when instantiating "%s". ' .
                    'Please specify argument explicitly.',
                    $this->parameter->getName(),
                    $this->getCallable(),
                )
            );
        }

        throw new NotInstantiableException(
            sprintf(
                'Can not determine value of the "%s" parameter of type "%s" when instantiating "%s". ' .
                'Please specify argument explicitly.',
                $this->parameter->getName(),
                $type,
                $this->getCallable(),
            )
        );
    }

    /**
     * Resolve union type string provided as a class name.
     *
     * @throws InvalidConfigException If an object of incorrect type was created.
     * @throws Throwable
     *
     * @return mixed|null Ready to use object or null if definition can
     * not be resolved and is marked as optional.
     */
    private function resolveUnionType(ContainerInterface $container)
    {
        /**
         * @psalm-suppress UndefinedClass
         *
         * @var ReflectionUnionType $parameterType
         */
        $parameterType = $this->parameter->getType();
        /**
         * @var \ReflectionType[] $types
         * @psalm-suppress UndefinedClass
         */
        $types = $parameterType->getTypes();
        $class = implode('|', $types);

        foreach ($types as $type) {
            if (!$type->isBuiltin()) {
                /** @var ReflectionNamedType $type */
                $typeName = $type->getName();
                if ($typeName === 'self') {
                    // If type name is "self", it means that called class and
                    // $parameter->getDeclaringClass() returned instance of `ReflectionClass`.
                    /** @psalm-suppress PossiblyNullReference */
                    $typeName = $this->parameter->getDeclaringClass()->getName();
                }
                try {
                    /** @var mixed */
                    $result = $container->get($typeName);
                    if (!$result instanceof $typeName) {
                        $actualType = $this->getValueType($result);
                        throw new InvalidConfigException(
                            "Container returned incorrect type \"$actualType\" for service \"$class\"."
                        );
                    }

                    return $result;
                } catch (Throwable $t) {
                    $error = $t;
                }
            }
        }

        if ($this->parameter->isOptional()) {
            return null;
        }

        if (!isset($error)) {
            return $this->resolveVariadicOrBuiltinOrNonTyped();
        }

        throw $error;
    }

    private function isUnionType(): bool
    {
        /** @psalm-suppress UndefinedClass */
        return $this->parameter->getType() instanceof ReflectionUnionType;
    }

    private function getType(): ?string
    {
        $type = $this->parameter->getType();

        /** @psalm-suppress UndefinedClass, TypeDoesNotContainType */
        if ($type instanceof ReflectionUnionType) {
            /** @var ReflectionNamedType[] */
            $namedTypes = $type->getTypes();
            $names = array_map(
                static fn (ReflectionNamedType $t) => $t->getName(),
                $namedTypes
            );
            return implode('|', $names);
        }

        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        return null;
    }

    private function getCallable(): string
    {
        $callable = [];

        $class = $this->parameter->getDeclaringClass();
        if ($class !== null) {
            $callable[] = $class->getName();
        }
        $callable[] = $this->parameter->getDeclaringFunction()->getName() . '()';

        return implode('::', $callable);
    }

    /**
     * Get type of the value provided.
     *
     * @param mixed $value Value to get type for.
     */
    private function getValueType($value): string
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }
}
