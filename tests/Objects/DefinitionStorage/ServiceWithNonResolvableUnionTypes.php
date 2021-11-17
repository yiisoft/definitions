<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Objects\DefinitionStorage;

final class ServiceWithNonResolvableUnionTypes
{
    public function __construct(ServiceWithNonExistingDependency|ServiceWithPrivateConstructor $class)
    {
    }
}
