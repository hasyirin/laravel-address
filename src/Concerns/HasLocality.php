<?php

declare(strict_types=1);

namespace Hasyirin\Address\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasLocality
{
    abstract public function scopeLocal(Builder $query): void;

    public static function local(): ?static
    {
        return static::query()->local()->first();
    }
}
