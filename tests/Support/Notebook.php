<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Tests\Support;

final class Notebook
{
    public function __construct(
        public \NotExist1|\NotExist2 $notExist,
    ) {}
}
