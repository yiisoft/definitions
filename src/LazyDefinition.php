<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Helpers\Normalizer;

final class LazyDefinition implements DefinitionInterface
{
    public function __construct(
        private mixed $definition,
        /**
         * @var class-string
         */
        private string $objectClass,
    ) {
    }

    /**
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public function resolve(ContainerInterface $container): mixed
    {
        /** @var LazyLoadingValueHolderFactory $factory */
        $factory = $container->get(LazyLoadingValueHolderFactory::class);
        /**
         * @var mixed $definition
         */
        $definition = $this->definition;
        $objectClass = $this->objectClass;

        /** @psalm-suppress InvalidArgument */
        return $factory->createProxy(
            $objectClass,
            function (mixed &$wrappedObject) use ($container, $objectClass, $definition) {
                $definition = Normalizer::normalize($definition, $objectClass);
                /**
                 * @var mixed $wrappedObject
                 */
                $wrappedObject = $definition->resolve($container);
            }
        );
    }
}
