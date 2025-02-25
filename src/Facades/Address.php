<?php

namespace Hasyirin\Address\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Hasyirin\Address\Address
 */
class Address extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Hasyirin\Address\Address::class;
    }
}
