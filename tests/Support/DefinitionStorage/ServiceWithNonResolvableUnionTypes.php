<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support\DefinitionStorage;

final class ServiceWithNonResolvableUnionTypes
{
    public function __construct(ServiceWithNonExistingDependency|ServiceWithPrivateConstructor $class)
    {
    }
}
