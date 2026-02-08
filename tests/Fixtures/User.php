<?php

namespace Hasyirin\Address\Tests\Fixtures;

use Hasyirin\Address\Concerns\InteractsWithAddresses;
use Hasyirin\Address\Contracts\Addressable;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements Addressable
{
    use InteractsWithAddresses;

    protected $fillable = ['name'];
}
