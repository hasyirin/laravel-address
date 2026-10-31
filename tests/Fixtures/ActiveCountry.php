<?php

namespace Hasyirin\Address\Tests\Fixtures;

use Hasyirin\Address\Models\Country;

class ActiveCountry extends Country
{
    protected $attributes = ['is_active' => true];
}
