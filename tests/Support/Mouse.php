<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class Mouse
{
    private ?string $name = null;
    private ?EngineInterface $engine = null;
    private array $colors = [];

    public function setNameAndEngine(string $name, EngineInterface $engine): void
    {
        $this->name = $name;
        $this->engine = $engine;
    }

    public function setNameAndColors(string $name, ...$colors): void
    {
        $this->name = $name;
        $this->colors = $colors;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getEngine(): ?EngineInterface
    {
        return $this->engine;
    }

    public function getColors(): array
    {
        return $this->colors;
    }
}
