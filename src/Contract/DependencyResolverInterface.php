<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Contract;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Dependency resolver is used to resolve dependencies of an object obtained from container.
 */
interface DependencyResolverInterface
{
    /**
     * Resolve a dependency with an ID specified.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface No entry was found for this identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed|object Entry.
     */
    public function resolve(string $id);

    /**
     * Resolve a reference with an ID specified.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface No entry was found for this identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed|object
     *
     * @psalm-suppress InvalidThrow
     */
    public function resolveReference(string $id);
}
