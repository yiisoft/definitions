<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Php8_4\AsymmetricVisibility;

final class PublicGet
{
    public private(set) string $privateVar = '';
    public protected(set) string $protectedVar = '';
}
