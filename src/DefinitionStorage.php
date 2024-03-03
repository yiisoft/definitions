<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Psr\Container\ContainerInterface;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;
use Throwable;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Helpers\DefinitionExtractor;

/**
 * Stores service definitions and checks if a definition could be instantiated.
 */
final class DefinitionStorage
{
    /**
     * @var array<string,1>
     */
    private array $buildStack = [];

    private ?ContainerInterface $delegateContainer = null;

    /**
     * @param array $definitions Definitions to store.
     * @param bool $useStrictMode If every dependency should be defined explicitly including classes.
     */
    public function __construct(
        private array $definitions = [],
        private bool $useStrictMode = false
    ) {
    }

    /**
     * @param ContainerInterface $delegateContainer Container to fall back to when dependency is not found.
     */
    public function setDelegateContainer(ContainerInterface $delegateContainer): void
    {
        $this->delegateContainer = $delegateContainer;
    }

    /**
     * Checks if there is a definition with ID specified and that it can be created.
     *
     * @param string $id class name, interface name or alias name
     *
     * @throws CircularReferenceException
     */
    public function has(string $id): bool
    {
        $this->buildStack = [];
        return $this->isResolvable($id, []);
    }

    /**
     * Returns a stack with definition IDs in the order the latest dependency obtained would be built.
     *
     * @return string[] Build stack.
     */
    public function getBuildStack(): array
    {
        return array_keys($this->buildStack);
    }

    /**
     * Get a definition with a given ID.
     *
     * @throws CircularReferenceException
     *
     * @return mixed|object Definition with a given ID.
     */
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new RuntimeException("Service $id doesn't exist in DefinitionStorage.");
        }
        return $this->definitions[$id];
    }

    /**
     * Set a definition.
     *
     * @param string $id ID to set definition for.
     * @param mixed $definition Definition to set.
     */
    public function set(string $id, mixed $definition): void
    {
        $this->definitions[$id] = $definition;
    }

    /**
     * @param array<string,1> $building
     *
     * @throws CircularReferenceException
     */
    private function isResolvable(string $id, array $building, ?string $parameterName = null): bool
    {
        if (isset($this->definitions[$id])) {
            return true;
        }

        if (
            $parameterName !== null
            && (
                isset($this->definitions[$typedParameterName = $id . ' $' . $parameterName])
                || isset($this->definitions[$typedParameterName = '$' . $parameterName])
            )
            && (!empty($buildingClass = array_key_last($building))) && class_exists($buildingClass)
        ) {
            $definition = $this->definitions[$buildingClass] ?? null;
            $temporaryDefinition = ArrayDefinition::fromConfig([
                ArrayDefinition::CLASS_NAME => $buildingClass,
                ArrayDefinition::CONSTRUCTOR => [
                    $parameterName => is_string($this->definitions[$typedParameterName])
                        ? Reference::to($this->definitions[$typedParameterName])
                        : $this->definitions[$typedParameterName],
                ],
            ]);
            if ($definition instanceof ArrayDefinition) {
                $this->definitions[$buildingClass] = $definition->merge($temporaryDefinition);
            } else {
                $this->definitions[$buildingClass] = $temporaryDefinition;
            }

            return true;
        }

        if ($this->useStrictMode || !class_exists($id)) {
            $this->buildStack += $building + [$id => 1];
            return false;
        }

        if (isset($building[$id])) {
            throw new CircularReferenceException(sprintf(
                'Circular reference to "%s" detected while building: %s.',
                $id,
                implode(', ', array_keys($building))
            ));
        }

        try {
            $dependencies = DefinitionExtractor::fromClassName($id);
        } catch (Throwable) {
            $this->buildStack += $building + [$id => 1];
            return false;
        }

        if ($dependencies === []) {
            $this->definitions[$id] = $id;
            return true;
        }

        $isResolvable = true;
        $building[$id] = 1;

        try {
            foreach ($dependencies as $dependency) {
                $parameter = $dependency->getReflection();
                $type = $parameter->getType();

                if ($parameter->isVariadic() || $parameter->isOptional()) {
                    /** @infection-ignore-all Mutation don't change behaviour, but degrade performance. */
                    break;
                }

                if (
                    ($type instanceof ReflectionNamedType && $type->isBuiltin())
                    || (!$type instanceof ReflectionNamedType && !$type instanceof ReflectionUnionType)
                ) {
                    $isResolvable = false;
                    break;
                }

                /** @var ReflectionNamedType|ReflectionUnionType $type */

                // Union type is used as type hint
                if ($type instanceof ReflectionUnionType) {
                    $isUnionTypeResolvable = false;
                    $unionTypes = [];
                    foreach ($type->getTypes() as $unionType) {
                        /**
                         * @psalm-suppress DocblockTypeContradiction Need for PHP 8.0 and 8.1 only
                         */
                        if (!$unionType instanceof ReflectionNamedType || $unionType->isBuiltin()) {
                            continue;
                        }

                        $typeName = $unionType->getName();
                        /**
                         * @psalm-suppress TypeDoesNotContainType
                         *
                         * @link https://github.com/vimeo/psalm/issues/6756
                         */
                        if ($typeName === 'self') {
                            continue;
                        }
                        $unionTypes[] = $typeName;
                        if ($this->isResolvable($typeName, $building, $parameter->getName())) {
                            $isUnionTypeResolvable = true;
                            /** @infection-ignore-all Mutation don't change behaviour, but degrade performance. */
                            break;
                        }
                    }

                    if (!$isUnionTypeResolvable) {
                        foreach ($unionTypes as $typeName) {
                            if ($this->delegateContainer !== null && $this->delegateContainer->has($typeName)) {
                                $isUnionTypeResolvable = true;
                                /** @infection-ignore-all Mutation don't change behaviour, but degrade performance. */
                                break;
                            }
                        }

                        $isResolvable = $isUnionTypeResolvable;
                        if (!$isResolvable) {
                            break;
                        }
                    }
                    continue;
                }

                // Our parameter has a class type hint
                if (!$type->isBuiltin()) {
                    $typeName = $type->getName();
                    /**
                     * @psalm-suppress TypeDoesNotContainType
                     *
                     * @link https://github.com/vimeo/psalm/issues/6756
                     */
                    if ($typeName === 'self') {
                        throw new CircularReferenceException(
                            sprintf(
                                'Circular reference to "%s" detected while building: %s.',
                                $id,
                                implode(', ', array_keys($building))
                            )
                        );
                    }

                    if (
                        !$this->isResolvable($typeName, $building, $parameter->getName())
                        && ($this->delegateContainer === null || !$this->delegateContainer->has($typeName))
                    ) {
                        $isResolvable = false;
                        break;
                    }
                }
            }
        } finally {
            $this->buildStack += $building;
        }

        if ($isResolvable && !isset($this->definitions[$id])) {
            $this->definitions[$id] = $id;
        }

        return $isResolvable;
    }
}
