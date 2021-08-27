<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

use Closure;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DependencyResolverInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class SimpleDependencyResolver implements DependencyResolverInterface
{
    private SimpleContainer $container;

    public function __construct(array $definitions = [], Closure $factory = null)
    {
        $definitions[ContainerInterface::class] ??= $this;
        $this->container = new SimpleContainer($definitions, $factory);
    }

    public function get(string $id)
    {
        return $this->container->get($id);
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    public function resolveReference(string $id)
    {
        return $this->get($id);
    }

    public function invoke(callable $callable)
    {
        return (new Injector($this))->invoke($callable);
    }
}
