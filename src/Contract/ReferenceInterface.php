<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Contract;

use Yiisoft\Definitions\Exception\InvalidConfigException;

/**
 * Reference points to another named defintion. Usually it is another service in the container
 * or another object defintion in the factory.
 */
interface ReferenceInterface extends DefinitionInterface
{
    /**
     * Create an instance of the reference pointing to ID specified.
     *
     * @param mixed $id ID of the service or object to point to.
     *
     * @throws InvalidConfigException
     */
    public static function to($id): self;
}
