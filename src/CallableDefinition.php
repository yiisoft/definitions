<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Closure;
use ReflectionFunction;
use ReflectionMethod;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Contract\DependencyResolverInterface;
use Yiisoft\Definitions\Infrastructure\DefinitionExtractor;
use Yiisoft\Definitions\Infrastructure\DefinitionResolver;

use function is_array;
use function is_object;

final class CallableDefinition implements DefinitionInterface
{
    /**
     * @var array|callable
     * @psalm-var callable|array{0:class-string,1:string}
     */
    private $method;

    /**
     * @param array|callable $method
     *
     * @psalm-param callable|array{0:class-string,1:string} $method
     */
    public function __construct($method)
    {
        $this->method = $method;
    }

    public function resolve(DependencyResolverInterface $container)
    {
        $callable = $this->prepareCallable($this->method, $container);
        $callable = Closure::fromCallable($callable);
        $reflection = new ReflectionFunction($callable);

        $dependencies = DefinitionExtractor::getInstance()->fromFunction($reflection);
        $arguments = DefinitionResolver::resolveArray($container, $dependencies);

        return $reflection->invokeArgs($arguments);
    }

    /**
     * @param array|callable $callable
     *
     * @psalm-param callable|array{0:class-string,1:string} $callable
     */
    private function prepareCallable($callable, DependencyResolverInterface $container): callable
    {
        if (is_array($callable) && !is_object($callable[0])) {
            $reflection = new ReflectionMethod($callable[0], $callable[1]);
            if (!$reflection->isStatic()) {
                /** @var mixed */
                $callable[0] = $container->get($callable[0]);
            }
        }

        return $callable;
    }
}
