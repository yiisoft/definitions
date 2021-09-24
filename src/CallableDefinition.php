<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\Infrastructure\DefinitionExtractor;
use Yiisoft\Definitions\Infrastructure\DefinitionResolver;

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
     * @param array|callable $callable Callable to be used for building
     * an object. Dependencies are determined and passed based
     * on the types of arguments in the callable signature.
     *
     * @psalm-param callable|array{0:class-string,1:string} $callable
     */
    public function __construct($callable)
    {
        $this->callable = $callable;
    }

    public function resolve(ContainerInterface $container)
    {
        try {
            $reflection = new ReflectionFunction(
                $this->prepareClosure($this->callable, $container)
            );
        } catch (ReflectionException $e) {
            throw new NotInstantiableException(
                'Can not instantiate callable definition. Got ' . var_export($this->callable, true)
            );
        }

        $dependencies = DefinitionExtractor::getInstance()->fromFunction($reflection);
        $arguments = DefinitionResolver::resolveArray($container, null, $dependencies);

        return $reflection->invokeArgs($arguments);
    }

    /**
     * @param array|callable $callable
     *
     * @psalm-param callable|array{0:class-string,1:string} $callable
     */
    private function prepareClosure($callable, ContainerInterface $container): Closure
    {
        if (is_array($callable) && !is_object($callable[0])) {
            $reflection = new ReflectionMethod($callable[0], $callable[1]);
            if (!$reflection->isStatic()) {
                /** @var mixed */
                $callable[0] = $container->get($callable[0]);
            }
        }

        return Closure::fromCallable($callable);
    }
}
