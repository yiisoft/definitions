<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support\Helper;

use Psr\Container\ContainerInterface;
use stdClass;

final class ReferenceResolver implements ContainerInterface
{
    private ?string $reference = null;
    private array $references = [];

    public function __construct(private ?object $mockObject = null)
    {
        $this->mockObject ??= new stdClass();
    }

    public function get(string $id)
    {
        $this->reference = $id;
        $this->references[] = $id;

        return $this->mockObject;
    }

    public function has(string $id): bool
    {
        return true;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getReferences(): array
    {
        return $this->references;
    }
}
