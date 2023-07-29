<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

class NotFinalClass
{
    private array $arguments;

    public function __construct(...$arguments)
    {
        $this->arguments = $arguments;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
