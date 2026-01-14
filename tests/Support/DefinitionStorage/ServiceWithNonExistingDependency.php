<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support\DefinitionStorage;

use NonExisting;

final class ServiceWithNonExistingDependency
{
    public function __construct(NonExisting $nonExisting) {}
}
