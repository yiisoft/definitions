<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support\DefinitionStorage;

final class ServiceWithNonExistingSubDependency
{
    public function __construct(ServiceWithNonExistingDependency $serviceWithInvalidDependency) {}
}
