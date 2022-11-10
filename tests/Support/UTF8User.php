<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class UTF8User
{
    public string $айди;
    private string $имя;

    public function установитьИмя(string $v): void
    {
        $this->имя = $v;
    }
}
