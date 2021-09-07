<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Throwable;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Contract\DependencyResolverInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;

use function get_class;
use function gettype;
use function is_object;

/**
 * Reference points to a class name in the container
 */
final class ClassDefinition implements DefinitionInterface
{
    private string $class;
    private bool $optional;

    /**
     * Constructor.
     *
     * @param string $class the class name
     * @param bool $optional if null should be returned instead of throwing an exception
     */
    public function __construct(string $class, bool $optional)
    {
        $this->class = $class;
        $this->optional = $optional;
    }

    public function getType(): string
    {
        return $this->class;
    }

    /**
     * @throws InvalidConfigException
     */
    public function resolve(DependencyResolverInterface $dependencyResolver)
    {
        if ($this->isUnionType()) {
            return $this->resolveUnionType($dependencyResolver);
        }

        try {
            /** @var mixed */
            $result = $dependencyResolver->resolve($this->class);
        } catch (Throwable $t) {
            if ($this->optional) {
                return null;
            }
            throw $t;
        }

        if (!$result instanceof $this->class) {
            $actualType = $this->getValueType($result);
            throw new InvalidConfigException(
                "Container returned incorrect type \"$actualType\" for service \"$this->class\"."
            );
        }
        return $result;
    }

    /**
     * @throws Throwable
     *
     * @return mixed
     */
    private function resolveUnionType(DependencyResolverInterface $dependencyResolver)
    {
        $types = explode('|', $this->class);

        foreach ($types as $type) {
            try {
                /** @var mixed */
                $result = $dependencyResolver->resolve($type);
                if (!$result instanceof $type) {
                    $actualType = $this->getValueType($result);
                    throw new InvalidConfigException(
                        "Container returned incorrect type \"$actualType\" for service \"$this->class\"."
                    );
                }
                return $result;
            } catch (Throwable $t) {
                $error = $t;
            }
        }

        if ($this->optional) {
            return null;
        }

        throw $error;
    }

    private function isUnionType(): bool
    {
        return strpos($this->class, '|') !== false;
    }

    /**
     * @param mixed $value
     */
    private function getValueType($value): string
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }
}
