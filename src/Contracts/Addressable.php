<?php

declare(strict_types=1);

namespace Hasyirin\Address\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use UnitEnum;

interface Addressable
{
    public function addresses(): MorphMany;

    public function address(string|UnitEnum $type = 'primary'): MorphOne;
}
