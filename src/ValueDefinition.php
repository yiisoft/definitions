<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;

/**
 * Value definition resolves value passed as is.
 */
final class ValueDefinition implements DefinitionInterface
{
    /**
     * @var mixed
     */
    private $value;

    private ?string $type;

    /**
     * @param mixed $value Value to be returned on resolving.
     * @param ?string $type Value type.
     */
    public function __construct($value, string $type = null)
    {
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * Get type of the value.
     *
     * @return string|null Value type.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    public function resolve(ContainerInterface $container)
    {
        return $this->value;
    }
}
