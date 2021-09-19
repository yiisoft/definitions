<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Contract;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotFoundException;
use Yiisoft\Definitions\Exception\NotInstantiableException;

/**
 * Definition is describing a way to create and configure a service or an object.
 */
interface DefinitionInterface
{
    /**
     * Resolve this definition.
     * 
     * @return mixed|null Ready to use object or null if definition can
     * not be resolved and is marked as optional.
     * @throws InvalidConfigException If an object of incorrect type was created.
     * @throws CircularReferenceException If there is a circular reference detected
     * when resolving the definition.
     * @throws NotFoundException If container does not know how to resolve
     * the definition.
     * @throws NotInstantiableException If an object can not be instantiated.
     */
    public function resolve(ContainerInterface $container);
}
