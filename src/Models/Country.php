<?php

declare(strict_types=1);

namespace Hasyirin\Address\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'alpha_2',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('address.tables.countries', parent::getTable());
    }

    public function states(): HasMany
    {
        return $this->hasMany(config('address.models.state'));
    }

    public function postOffices(): HasMany
    {
        return $this->hasMany(config('address.models.post-office'));
    }

    public function districts(): HasManyThrough
    {
        return $this->hasManyThrough(config('address.models.district'), config('address.models.state'));
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(config('address.models.address'));
    }
}
