<?php

declare(strict_types=1);

namespace Hasyirin\Address\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait InteractsWithCodeScope
{
    public function scopeOfCode(Builder $query, string $code): ?self
    {
        return $query->firstWhere('code', $code);
    }
}
