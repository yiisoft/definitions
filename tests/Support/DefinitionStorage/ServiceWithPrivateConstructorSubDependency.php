<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support\DefinitionStorage;

final class ServiceWithPrivateConstructorSubDependency
{
    public function __construct(ServiceWithPrivateConstructor $serviceWithPrivateConstructor) {}
}
