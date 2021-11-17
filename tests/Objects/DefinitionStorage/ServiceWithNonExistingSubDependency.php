<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Objects\DefinitionStorage;

final class ServiceWithNonExistingSubDependency
{
    public function __construct(ServiceWithNonExistingDependency $serviceWithInvalidDependency)
    {
    }
}
