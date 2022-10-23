<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\ReferenceInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;

use function is_string;

/**
 * The `Reference` defines a dependency to a service in the container or factory in another service definition.
 * For example:
 *
 * ```php
 * [
 *    InterfaceA::class => ConcreteA::class,
 *    'alternativeForA' => ConcreteB::class,
 *    MyService::class => [
 *        '__construct()' => [
 *            Reference::to('alternativeForA'),
 *        ],
 *    ],
 * ]
 * ```
 */
final class Reference implements ReferenceInterface
{
    private string $id;
    private bool $optional;

    /**
     * @param mixed $id
     *
     * @throws InvalidConfigException
     */
    private function __construct($id, bool $optional)
    {
        if (!is_string($id)) {
            throw new InvalidConfigException('Reference ID must be string.');
        }

        $this->id = $id;
        $this->optional = $optional;
    }

    /**
     * @throws InvalidConfigException If ID is not string.
     */
    public static function to($id): self
    {
        return new self($id, false);
    }

    /**
     * @param mixed $id ID of the service or object to point to.
     *
     * @throws InvalidConfigException If ID is not string.
     */
    public static function optional($id): self
    {
        return new self($id, true);
    }

    public function resolve(ContainerInterface $container)
    {
        return (!$this->optional || $container->has($this->id)) ? $container->get($this->id) : null;
    }
}
