<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Helpers\DefinitionResolver;

final class LazyDefinitionDecorator implements DefinitionInterface
{
    public function __construct(
        private LazyLoadingValueHolderFactory $factory,
        private mixed $definition,
        private string $objectClass,
    ) {
    }

    public function resolve(ContainerInterface $container): mixed
    {
        return $this->factory->createProxy(
            $this->objectClass,
            function (&$wrappedObject) use ($container) {
                $wrappedObject = DefinitionResolver::resolve($container, null, $this->definition);
            }
        );
    }
}
