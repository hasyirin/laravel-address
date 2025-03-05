<?php

declare(strict_types=1);

namespace Hasyirin\Address\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait InteractsWithAddresses
{
    public function getAddress(string $type): MorphOne
    {
        return $this->morphOne(config('address.models.address'), 'addressable')
            ->withDefault(['type' => $type])
            ->where('type', $type);
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(config('address.models.address'), 'addressable');
    }

    public function address(): MorphOne
    {
        return $this->getAddress('primary');
    }
}
