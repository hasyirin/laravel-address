<?php

declare(strict_types=1);

namespace Hasyirin\Address\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface Addressable
{
    public function getAddress(string $type): MorphOne;

    public function addresses(): MorphMany;

    public function address(): MorphOne;
}
