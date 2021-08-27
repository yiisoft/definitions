<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Contract;

use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotFoundException;
use Yiisoft\Definitions\Exception\NotInstantiableException;

/**
 * Interface DefinitionInterface
 */
interface DefinitionInterface
{
    /**
     * @param DependencyResolverInterface $container
     *
     * @throws CircularReferenceException
     * @throws NotFoundException
     * @throws NotInstantiableException
     * @throws InvalidConfigException
     *
     * @return mixed|object
     */
    public function resolve(DependencyResolverInterface $container);
}
