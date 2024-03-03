<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\VirtualProxyInterface;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Helpers\Normalizer;

final class LazyDefinition implements DefinitionInterface
{
    public function __construct(
        private mixed $definition,
        /**
         * @psalm-var class-string
         */
        private string $class,
    ) {
    }

    public function resolve(ContainerInterface $container): VirtualProxyInterface
    {
        /** @var LazyLoadingValueHolderFactory $factory */
        $factory = $container->get(LazyLoadingValueHolderFactory::class);
        $definition = $this->definition;

        return $factory->createProxy(
            $this->class,
            static function (mixed &$wrappedObject) use ($container, $definition) {
                $definition = Normalizer::normalize($definition);
                $wrappedObject = $definition->resolve($container);
                return true;
            }
        );
    }
}
