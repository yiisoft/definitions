<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Infrastructure;

use ReflectionClass;
use ReflectionException;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use Yiisoft\Definitions\Exception\NotFoundException;
use Yiisoft\Definitions\Exception\NotInstantiableClassException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\ParameterDefinition;

/**
 * This class extracts dependency definitions from type hints of a function or a class constructor parameters.
 * Note that service names need not match the parameter names, parameter names are ignored.
 *
 * @internal
 */
final class DefinitionExtractor
{
    private static ?self $instance = null;

    /**
     * @psalm-var array<string, array<string, ParameterDefinition>>
     */
    private static array $dependencies = [];

    private function __construct()
    {
    }

    /**
     * Get an instance of this class or create it.
     *
     * @return static An instance of this class.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Extract dependency definitions from type hints of a class constructor parameters.
     *
     * @psalm-param class-string $class
     *
     * @throws NotFoundException
     * @throws NotInstantiableException
     *
     * @return ParameterDefinition[]
     * @psalm-return array<string, ParameterDefinition>
     */
    public function fromClassName(string $class): array
    {
        if (isset(self::$dependencies[$class])) {
            return self::$dependencies[$class];
        }

        try {
            $reflectionClass = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new NotFoundException($class);
        }

        if (!$reflectionClass->isInstantiable()) {
            throw new NotInstantiableClassException($class);
        }

        $constructor = $reflectionClass->getConstructor();
        $dependencies = $constructor === null ? [] : $this->fromFunction($constructor);
        self::$dependencies[$class] = $dependencies;

        return $dependencies;
    }

    /**
     * Extract dependency definitions from type hints of a function.
     *
     * @return ParameterDefinition[]
     * @psalm-return array<string, ParameterDefinition>
     */
    public function fromFunction(ReflectionFunctionAbstract $reflectionFunction): array
    {
        $result = [];
        foreach ($reflectionFunction->getParameters() as $parameter) {
            $result[$parameter->getName()] = $this->fromParameter($parameter);
        }
        return $result;
    }

    private function fromParameter(ReflectionParameter $parameter): ParameterDefinition
    {
        return new ParameterDefinition($parameter);
    }
}
