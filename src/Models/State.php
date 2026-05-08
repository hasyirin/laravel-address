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

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('address.tables.states', parent::getTable());
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(config('address.models.country'));
    }

    public function districts(): HasMany
    {
        return $this->hasMany(config('address.models.district'));
    }

    public function postOffices(): HasMany
    {
        return $this->hasMany(config('address.models.post-office'));
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(config('address.models.address'));
    }

    public function scopeLocal(Builder $query): void
    {
        $query->where('code', config('address.locality.state'))
            ->whereHas('country', fn (Builder $q) => $q->where('code', config('address.locality.country')));
    }

    /**
     * @return array<int, class-string>
     */
    protected static function localityDescendants(): array
    {
        return [config('address.models.district')];
    }
}
