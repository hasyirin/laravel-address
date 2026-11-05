<?php

use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Tests\Fixtures\Branch;

use function PHPStan\Testing\assertType;

function scopeTypes(): void
{
    assertType('Illuminate\Database\Eloquent\Builder<Hasyirin\Address\Models\PostOffice>', PostOffice::query()->local());
    assertType('Illuminate\Database\Eloquent\Builder<Hasyirin\Address\Tests\Fixtures\Branch>', Branch::query()->local());
}
