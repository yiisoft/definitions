<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support\DefinitionStorage;

final class ServiceWithNonExistingDependency
{
    public function __construct(\NonExisting $nonExisting)
    {
    }
}
