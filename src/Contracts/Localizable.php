<?php

declare(strict_types=1);

namespace Hasyirin\Address\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Localizable
{
    public function scopeLocal(Builder $query): void;

    public static function local(): ?static;
}
