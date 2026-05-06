<?php

declare(strict_types=1);

namespace Hasyirin\Address\Models;

use Hasyirin\Address\Concerns\HasLocality;
use Hasyirin\Address\Contracts\Localizable;
use Hasyirin\Address\Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $alpha_2
 * @property Collection $states
 * @property Collection $districts
 * @property Collection $postOffices
 * @property Collection $addresses
 */
class Country extends Model implements Localizable
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory, HasLocality, SoftDeletes;

    protected static function newFactory(): CountryFactory
    {
        return CountryFactory::new();
    }

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

    public function districts(): HasManyThrough
    {
        return $this->hasManyThrough(config('address.models.district'), config('address.models.state'));
    }

    public function postOffices(): HasManyThrough
    {
        return $this->hasManyThrough(config('address.models.post-office'), config('address.models.state'));
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(config('address.models.address'));
    }

    public function scopeLocal(Builder $query): void
    {
        $query->where('code', config('address.locality.country'));
    }
}
