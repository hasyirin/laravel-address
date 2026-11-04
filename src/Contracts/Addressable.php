<?php

declare(strict_types=1);

namespace Hasyirin\Address\Contracts;

use Hasyirin\Address\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use UnitEnum;

interface Addressable
{
    /** @return MorphMany<Address, covariant Model> */
    public function addresses(): MorphMany;

    /** @return MorphOne<Address, covariant Model> */
    public function address(string|UnitEnum $type = 'primary'): MorphOne;
}
