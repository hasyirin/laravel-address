<?php

declare(strict_types=1);

namespace Hasyirin\Address\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use UnitEnum;

use function Illuminate\Support\enum_value;

trait InteractsWithAddresses
{
    public function addresses(): MorphMany
    {
        return $this->morphMany(config('address.models.address'), 'addressable');
    }

    public function address(string|UnitEnum $type = 'primary'): MorphOne
    {
        $type = (string) enum_value($type);

        return $this->morphOne(config('address.models.address'), 'addressable')
            ->withDefault(['type' => $type])
            ->withAttributes(['type' => $type]);
    }
}
