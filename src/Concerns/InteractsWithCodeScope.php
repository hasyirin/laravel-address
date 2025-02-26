<?php

declare(strict_types=1);

namespace Hasyirin\Address\Concerns;

trait InteractsWithCodeScope
{
    public static function ofCode(string $code): ?static
    {
        return static::query()->firstWhere('code', $code);
    }
}
