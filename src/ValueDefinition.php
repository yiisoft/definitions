<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Contract\DependencyResolverInterface;

final class ValueDefinition implements DefinitionInterface
{
    /**
     * @var mixed
     */
    private $value;

    private ?string $type;

    /**
     * @param mixed $value
     */
    public function __construct($value, string $type = null)
    {
        $this->value = $value;
        $this->type = $type;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function resolve(DependencyResolverInterface $dependencyResolver)
    {
        return $this->value;
    }
}
