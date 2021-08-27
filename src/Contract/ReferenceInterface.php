<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Contract;

use Yiisoft\Definitions\Exception\InvalidConfigException;

interface ReferenceInterface extends DefinitionInterface
{
    /**
     * @param mixed $id
     *
     * @throws InvalidConfigException
     */
    public static function to($id): self;
}
