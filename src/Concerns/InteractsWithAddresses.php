<?php

declare(strict_types=1);

namespace Hasyirin\Address\Concerns;

use Hasyirin\Address\Models\Address;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use UnitEnum;

use function Illuminate\Support\enum_value;

trait InteractsWithAddresses
{
    /** @return MorphMany<Address, $this> */
    public function addresses(): MorphMany
    {
        /** @var class-string<Address> $model */
        $model = config('address.models.address');

        return $this->morphMany($model, 'addressable');
    }

    /** @return MorphOne<Address, $this> */
    public function address(string|UnitEnum $type = 'primary'): MorphOne
    {
        /** @var class-string<Address> $model */
        $model = config('address.models.address');
        $type = (string) enum_value($type);

        return $this->morphOne($model, 'addressable')
            ->withDefault(['type' => $type])
            ->withAttributes(['type' => $type]);
    }
}
