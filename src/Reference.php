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

    private function __construct(string $id, bool $optional)
    {
        $this->id = $id;
        $this->optional = $optional;
    }

    /**
     * @throws InvalidConfigException If ID is not string.
     */
    public static function to($id, bool $optional = false): self
    {
        if (!is_string($id)) {
            throw new InvalidConfigException('Reference ID must be string.');
        }

        return new self($id, $optional);
    }

    public function resolve(ContainerInterface $container)
    {
        return (!$this->optional || $container->has($this->id)) ? $container->get($this->id) : null;
    }
}
