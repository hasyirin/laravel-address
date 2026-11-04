<?php

declare(strict_types=1);

namespace Hasyirin\Address\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** @template TModel of Model */
interface Localizable
{
    /** @param Builder<TModel> $query */
    public function scopeLocal(Builder $query): void;

    public static function local(): ?static;
}
