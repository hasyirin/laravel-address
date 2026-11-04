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
 * @property ?string $alpha_2
 * @property Collection<int, State> $states
 * @property Collection<int, District> $districts
 * @property Collection<int, PostOffice> $postOffices
 * @property Collection<int, Address> $addresses
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

    public function getTable(): string
    {
        return $this->table ?? config('address.tables.countries', parent::getTable());
    }

    /** @return HasMany<State, $this> */
    public function states(): HasMany
    {
        /** @var class-string<State> $model */
        $model = config('address.models.state');

        return $this->hasMany($model, 'country_id');
    }

    /** @return HasManyThrough<District, State, $this> */
    public function districts(): HasManyThrough
    {
        /** @var class-string<District> $model */
        $model = config('address.models.district');
        /** @var class-string<State> $through */
        $through = config('address.models.state');

        return $this->hasManyThrough($model, $through, 'country_id', 'state_id');
    }

    /** @return HasManyThrough<PostOffice, State, $this> */
    public function postOffices(): HasManyThrough
    {
        /** @var class-string<PostOffice> $model */
        $model = config('address.models.post-office');
        /** @var class-string<State> $through */
        $through = config('address.models.state');

        return $this->hasManyThrough($model, $through, 'country_id', 'state_id');
    }

    /** @return HasMany<Address, $this> */
    public function addresses(): HasMany
    {
        /** @var class-string<Address> $model */
        $model = config('address.models.address');

        return $this->hasMany($model, 'country_id');
    }

    public function scopeLocal(Builder $query): void
    {
        $query->where($query->qualifyColumn('code'), static::localityCode('country'));
    }

    /**
     * @return array<int, string>
     */
    protected static function localityLevels(): array
    {
        return ['country'];
    }
}
