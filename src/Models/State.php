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
 * @property Collection $districts
 * @property Collection $addresses
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
        return $this->table ?? config('address.tables.states', parent::getTable());
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(config('address.models.country'));
    }

    public function districts(): HasMany
    {
        return $this->hasMany(config('address.models.district'), 'state_id');
    }

    public function postOffices(): HasMany
    {
        return $this->hasMany(config('address.models.post-office'), 'state_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(config('address.models.address'), 'state_id');
    }

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
