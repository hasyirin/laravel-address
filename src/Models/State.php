<?php

declare(strict_types=1);

namespace Hasyirin\Address\Models;

use Hasyirin\Address\Concerns\HasLocality;
use Hasyirin\Address\Contracts\Localizable;
use Hasyirin\Address\Database\Factories\StateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $country_id
 * @property string $code
 * @property string $name
 * @property Country $country
 * @property Collection<int, District> $districts
 * @property Collection<int, Address> $addresses
 *
 * @implements Localizable<State>
 */
class State extends Model implements Localizable
{
    /** @use HasFactory<StateFactory> */
    use HasFactory, HasLocality, SoftDeletes;

    protected static function newFactory(): StateFactory
    {
        return StateFactory::new();
    }

    protected $fillable = [
        'country_id',
        'code',
        'name',
    ];

    public function getTable(): string
    {
        return $this->table ?? config()->string('address.tables.states', parent::getTable());
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        /** @var class-string<Country> $model */
        $model = config('address.models.country');

        return $this->belongsTo($model);
    }

    /** @return HasMany<District, $this> */
    public function districts(): HasMany
    {
        /** @var class-string<District> $model */
        $model = config('address.models.district');

        return $this->hasMany($model, 'state_id');
    }

    /** @return HasMany<PostOffice, $this> */
    public function postOffices(): HasMany
    {
        /** @var class-string<PostOffice> $model */
        $model = config('address.models.post-office');

        return $this->hasMany($model, 'state_id');
    }

    /** @return HasMany<Address, $this> */
    public function addresses(): HasMany
    {
        /** @var class-string<Address> $model */
        $model = config('address.models.address');

        return $this->hasMany($model, 'state_id');
    }

    /** @param Builder<State> $query */
    public function scopeLocal(Builder $query): void
    {
        $query->where($query->qualifyColumn('code'), static::localityCode('state'))
            ->whereHas('country', fn (Builder $q) => $q->where('code', static::localityCode('country')));
    }

    /**
     * @return array<int, string>
     */
    protected static function localityLevels(): array
    {
        return ['country', 'state'];
    }
}
