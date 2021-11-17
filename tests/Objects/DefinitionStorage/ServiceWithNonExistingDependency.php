<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Objects\DefinitionStorage;

final class ServiceWithNonExistingDependency
{
    public function __construct(\NonExisting $nonExisting)
    {
    }
}
