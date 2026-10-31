<?php

namespace Hasyirin\Address\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class PlainCountry extends Model
{
    protected $fillable = ['code', 'name', 'alpha_2'];

    public function getTable(): string
    {
        return config('address.tables.countries');
    }
}
