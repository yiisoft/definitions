<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Psr\Container\ContainerInterface;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Throwable;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\Exception\InvalidConfigException;

/**
 * Parameter definition resolves an object based on information from `ReflectionParameter` instance.
 */
final class ParameterDefinition implements DefinitionInterface
{
    public function __construct(
        private ReflectionParameter $parameter
    ) {
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

    public function hasValue(): bool
    {
        return $this->parameter->isDefaultValueAvailable();
    }

    public function resolve(ContainerInterface $container): mixed
    {
        $type = $this->parameter->getType();

        if ($type === null || $this->isVariadic()) {
            return $this->resolveVariadicOrBuiltinOrNonTyped();
        }

        if ($this->isUnionType()) {
            return $this->resolveUnionType($container);
        }

        /** @var ReflectionNamedType|null $type */
        $type = $this->parameter->getType();
        $isBuiltin = $type !== null && $type->isBuiltin();

        if (!$isBuiltin) {
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
                if (
                    $this->parameter->isOptional()
                    && (
                        $t instanceof CircularReferenceException
                        || !$container->has($typeName)
                    )
                ) {
                    return $this->parameter->getDefaultValue();
                }
                throw $t;
            }

            if (!$result instanceof $typeName) {
                $actualType = get_debug_type($result);
                throw new InvalidConfigException(
                    "Container returned incorrect type \"$actualType\" for service \"{$type->getName()}\"."
                );
            }
            return $result;
        }

        return $this->resolveVariadicOrBuiltinOrNonTyped();
    }

    /**
     * @throws NotInstantiableException
     */
    private function resolveVariadicOrBuiltinOrNonTyped(): mixed
    {
        if ($this->parameter->isDefaultValueAvailable()) {
            return $this->parameter->getDefaultValue();
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
     * @return mixed Ready to use object or null if definition can not be resolved and is marked as optional.
     */
    private function resolveUnionType(ContainerInterface $container): mixed
    {
        /**
         * @var ReflectionUnionType $parameterType
         */
        $parameterType = $this->parameter->getType();

        /**
         * @var ReflectionNamedType[] $types
         */
        $types = $parameterType->getTypes();
        $class = implode('|', $types);

        foreach ($types as $type) {
            if (!$type->isBuiltin()) {
                $typeName = $type->getName();
                /**
                 * @psalm-suppress TypeDoesNotContainType
                 *
                 * @link https://github.com/vimeo/psalm/issues/6756
                 */
                if ($typeName === 'self') {
                    // If type name is "self", it means that called class and
                    // $parameter->getDeclaringClass() returned instance of `ReflectionClass`.
                    /** @psalm-suppress PossiblyNullReference */
                    $typeName = $this->parameter->getDeclaringClass()->getName();
                }

                try {
                    /** @var mixed */
                    $result = $container->get($typeName);
                    $resolved = true;
                } catch (Throwable $t) {
                    $error = $t;
                    $resolved = false;
                }

                if ($resolved) {
                    /** @var mixed $result Exist, because $resolved is true */
                    if (!$result instanceof $typeName) {
                        $actualType = get_debug_type($result);
                        throw new InvalidConfigException(
                            "Container returned incorrect type \"$actualType\" for service \"$class\"."
                        );
                    }
                    return $result;
                }

                /** @var Throwable $error Exist, because $resolved is false */
                if (
                    !$error instanceof CircularReferenceException
                    && $container->has($typeName)
                ) {
                    throw $error;
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
        return $this->parameter->getType() instanceof ReflectionUnionType;
    }

    private function getType(): ?string
    {
        $type = $this->parameter->getType();

        if ($type instanceof ReflectionUnionType) {
            $namedTypes = $type->getTypes();
            /** @infection-ignore-all Mutation don't change behaviour, but degrade performance. */
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
        $callable[] = $this->parameter
                ->getDeclaringFunction()
                ->getName() .
            '()';

        return implode('::', $callable);
    }
}
