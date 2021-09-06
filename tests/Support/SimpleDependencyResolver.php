<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

use Closure;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DependencyResolverInterface;
use Yiisoft\Definitions\Exception\NotFoundException;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class SimpleDependencyResolver implements DependencyResolverInterface
{
    private SimpleContainer $container;

    public function __construct(array $definitions = [], Closure $factory = null)
    {
        $this->container = new SimpleContainer(
            $definitions,
            $factory ?? function (string $id) {
                if ($id === ContainerInterface::class) {
                    return $this->container;
                }
                throw new NotFoundException($id);
            }
        );
    }

    public function resolve(string $id)
    {
        return $this->container->get($id);
    }

    public function resolveReference(string $id)
    {
        return $this->resolve($id);
    }
}
