<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Psr\Container\ContainerInterface;
use Throwable;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;

use function get_class;
use function gettype;
use function is_object;

/**
 * Points to a class or interface name.
 * Union type could be used as well.
 */
final class ClassDefinition implements DefinitionInterface
{
    private string $class;
    private bool $optional;

    /**
     * @param string $class A class or interface name. Union type could be used as well.
     * @param bool $optional If null should be returned instead of throwing an exception.
     */
    public function __construct(string $class, bool $optional)
    {
        $this->class = $class;
        $this->optional = $optional;
    }

    /**
     * Get type of the object to be created.
     *
     * @return string An interface or a class name.
     */
    public function getType(): string
    {
        return $this->class;
    }

    public function resolve(ContainerInterface $container)
    {
        if ($this->isUnionType()) {
            return $this->resolveUnionType($container);
        }

        try {
            /** @var mixed */
            $result = $container->get($this->class);
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
        $types = explode('|', $this->class);

        foreach ($types as $type) {
            try {
                /** @var mixed */
                $result = $container->get($type);
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
     * Get type of the value provided.
     * 
     * @param mixed $value Value to get type for.
     */
    private function getValueType($value): string
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }
}
