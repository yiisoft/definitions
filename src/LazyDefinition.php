<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Di\Helpers\DefinitionNormalizer;

final class LazyDefinition implements DefinitionInterface
{
    public function __construct(
        private mixed $definition,
        private string $objectClass,
    ) {
    }

    public function resolve(ContainerInterface $container): mixed
    {
        $factory = $container->get(LazyLoadingValueHolderFactory::class);
        $definition = $this->definition;
        $objectClass = $this->objectClass;

        return $factory->createProxy(
            $objectClass,
            function (&$wrappedObject) use ($container, $objectClass, $definition) {
                $definition = DefinitionNormalizer::normalize($definition, $objectClass);
                $wrappedObject = $definition->resolve($container);
            }
        );
    }
}
