<?php

declare(strict_types=1);

namespace Hasyirin\Address\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait InteractsWithCodeScope
{
    public function scopeOfCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    public static function ofCode(string $code): ?static
    {
        return static::query()->ofCode($code)->first();
    }
}
