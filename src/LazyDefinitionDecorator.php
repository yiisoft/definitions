<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Di\Helpers\DefinitionNormalizer;

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
                $definition = DefinitionNormalizer::normalize($this->definition, $this->objectClass);

                $wrappedObject = $definition->resolve($container);
            }
        );
    }
}
