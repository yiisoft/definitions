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
        /**
         * @var mixed $definition
         */
        $definition = $this->definition;
        $class = $this->class;

        return $factory->createProxy(
            $class,
            static function (mixed &$wrappedObject) use ($container, $class, $definition) {
                $definition = Normalizer::normalize($definition, $class);
                /**
                 * @var mixed $wrappedObject
                 */
                $wrappedObject = $definition->resolve($container);
                return true;
            }
        );
    }
}
