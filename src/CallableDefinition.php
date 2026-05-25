<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\Helpers\DefinitionExtractor;
use Yiisoft\Definitions\Helpers\DefinitionResolver;

use function get_class;
use function is_array;
use function is_object;

/**
 * Builds an object by executing a callable injecting
 * dependencies based on types used in its signature.
 */
final class CallableDefinition implements DefinitionInterface
{
    /**
     * @var array|callable
     * @psalm-var callable|array{0:class-string,1:string}
     */
    private $callable;

    /**
     * @var array<string,array<string,ParameterDefinition>>
     */
    private array $dependencies = [];

    /**
     * @var array<string,ParameterDefinition>|null
     */
    private ?array $callableDependencies = null;

    /**
     * @param array|callable $callable Callable to be used for building
     * an object. Dependencies are determined and passed based
     * on the types of arguments in the callable signature.
     *
     * @psalm-param callable|array{0:class-string,1:string} $callable
     */
    public function __construct(array|callable $callable)
    {
        $this->callable = $callable;
    }

    public function resolve(ContainerInterface $container): mixed
    {
        try {
            [$closure, $dependencies] = $this->prepare($this->callable, $container);
        } catch (ReflectionException) {
            throw new NotInstantiableException(
                'Can not instantiate callable definition. Got ' . var_export($this->callable, true),
            );
        }

        $arguments = DefinitionResolver::resolveArray($container, null, $dependencies);

        return $closure(...$arguments);
    }

    /**
     * @psalm-param callable|array{0:class-string,1:string} $callable
     *
     * @psalm-return array{Closure,array<string,ParameterDefinition>}
     */
    private function prepare(array|callable $callable, ContainerInterface $container): array
    {
        if (is_array($callable)) {
            $reflection = new ReflectionMethod($callable[0], $callable[1]);
            if (!is_object($callable[0]) && !$reflection->isStatic()) {
                $class = $callable[0];
                /** @var object $object */
                $object = $container->get($callable[0]);
                $callable[0] = $object;
                if (get_class($object) !== $class) {
                    $reflection = new ReflectionMethod($object, $callable[1]);
                }
            }

            return [
                Closure::fromCallable($callable),
                $this->getDependencies($this->getArrayCallableDependenciesKey($callable), $reflection),
            ];
        }

        $closure = Closure::fromCallable($callable);

        return [
            $closure,
            $this->callableDependencies ??= DefinitionExtractor::fromFunction(new ReflectionFunction($closure)),
        ];
    }

    /**
     * @param array{0:class-string|object,1:string} $callable
     */
    private function getArrayCallableDependenciesKey(array $callable): string
    {
        return (is_object($callable[0]) ? get_class($callable[0]) : $callable[0]) . "\0" . $callable[1];
    }

    /**
     * @return array<string,ParameterDefinition>
     */
    private function getDependencies(string $key, ReflectionFunctionAbstract $reflection): array
    {
        return $this->dependencies[$key] ??= DefinitionExtractor::fromFunction($reflection);
    }
}
