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
    private array $definitions;
    private array $buildStack = [];
    /** @psalm-suppress  PropertyNotSetInConstructor */
    private ?ContainerInterface $delegateContainer = null;
    private bool $useStrictMode;

    /**
     * @param array $definitions Definitions to store.
     * @param bool $useStrictMode If every dependency should be defined explicitly including classes.
     */
    public function __construct(array $definitions = [], bool $useStrictMode = false)
    {
        $this->definitions = $definitions;
        $this->useStrictMode = $useStrictMode;
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
     * Returns a stack with definition IDs as keys and 1 as values in the order the latest dependency obtained would
     * be built.
     *
     * @return array Build stack.
     */
    public function getBuildStack(): array
    {
        return $this->buildStack;
    }

    /**
     * Get a definition with a given ID.
     *
     * @return mixed|object Definition with a given ID.
     */
    public function get(string $id)
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
     * @param mixed|object $definition Definition to set.
     */
    public function set(string $id, $definition): void
    {
        $this->definitions[$id] = $definition;
    }

    private function isResolvable(string $id, array $building): bool
    {
        if (isset($this->definitions[$id])) {
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
        } catch (Throwable $e) {
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
                    break;
                }

                /**
                 * @var ReflectionNamedType|ReflectionUnionType|null $type
                 * @psalm-suppress RedundantConditionGivenDocblockType
                 * @psalm-suppress UndefinedClass
                 */
                if ($type === null || (!$type instanceof ReflectionUnionType && $type->isBuiltin())) {
                    $isResolvable = false;
                    break;
                }

                // PHP 8 union type is used as type hint
                /** @psalm-suppress UndefinedClass, TypeDoesNotContainType */
                if ($type instanceof ReflectionUnionType) {
                    $isUnionTypeResolvable = false;
                    $unionTypes = [];
                    /** @var ReflectionNamedType $unionType */
                    foreach ($type->getTypes() as $unionType) {
                        if (!$unionType->isBuiltin()) {
                            $typeName = $unionType->getName();
                            if ($typeName === 'self') {
                                continue;
                            }
                            $unionTypes[] = $typeName;
                            if ($this->isResolvable($typeName, $building)) {
                                $isUnionTypeResolvable = true;
                                break;
                            }
                        }
                    }


                    if (!$isUnionTypeResolvable) {
                        foreach ($unionTypes as $typeName) {
                            if ($this->delegateContainer !== null && $this->delegateContainer->has($typeName)) {
                                $isUnionTypeResolvable = true;
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

                /** @var ReflectionNamedType|null $type */
                // Our parameter has a class type hint
                if ($type !== null && !$type->isBuiltin()) {
                    $typeName = $type->getName();
                    /**
                     * @psalm-suppress TypeDoesNotContainType
                     *
                     * @link https://github.com/vimeo/psalm/issues/6756
                     */
                    if ($typeName === 'self') {
                        throw new CircularReferenceException(sprintf(
                            'Circular reference to "%s" detected while building: %s.',
                            $id,
                            implode(', ', array_keys($building))
                        ));
                    }

                    /** @psalm-suppress RedundantPropertyInitializationCheck */
                    if (!$this->isResolvable($typeName, $building) && ($this->delegateContainer === null || !$this->delegateContainer->has($typeName))) {
                        $isResolvable = false;
                        break;
                    }
                }
            }
        } finally {
            $this->buildStack += $building;
            unset($building[$id]);
        }

        if ($isResolvable) {
            $this->definitions[$id] = $id;
        }

        return $isResolvable;
    }
}
