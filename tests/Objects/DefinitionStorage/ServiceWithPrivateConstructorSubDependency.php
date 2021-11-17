<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Objects\DefinitionStorage;

final class ServiceWithPrivateConstructorSubDependency
{
    public function __construct(ServiceWithPrivateConstructor $serviceWithPrivateConstructor)
    {
    }
}
