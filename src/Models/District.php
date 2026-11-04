<?php

declare(strict_types=1);

namespace Hasyirin\Address\Models;

use Hasyirin\Address\Concerns\HasLocality;
use Hasyirin\Address\Contracts\Localizable;
use Hasyirin\Address\Database\Factories\DistrictFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $state_id
 * @property string $code
 * @property string $name
 * @property State $state
 * @property Collection<int, Subdistrict> $subdistricts
 */
class District extends Model implements Localizable
{
    /** @use HasFactory<DistrictFactory> */
    use HasFactory, HasLocality, SoftDeletes;

    protected static function newFactory(): DistrictFactory
    {
        return DistrictFactory::new();
    }

    protected $fillable = [
        'state_id',
        'code',
        'name',
    ];

    public function getTable(): string
    {
        return $this->table ?? config('address.tables.districts', parent::getTable());
    }

    /** @return BelongsTo<State, $this> */
    public function state(): BelongsTo
    {
        /** @var class-string<State> $model */
        $model = config('address.models.state');

        return $this->belongsTo($model);
    }

    /** @return HasMany<Subdistrict, $this> */
    public function subdistricts(): HasMany
    {
        /** @var class-string<Subdistrict> $model */
        $model = config('address.models.subdistrict');

        return $this->hasMany($model, 'district_id');
    }

    public function scopeLocal(Builder $query): void
    {
        $query->where($query->qualifyColumn('code'), static::localityCode('district'))
            ->whereHas('state', function (Builder $q) {
                $q->where('code', static::localityCode('state'))
                    ->whereHas('country', fn (Builder $r) => $r->where('code', static::localityCode('country')));
            });
    }

    /**
     * @return array<int, string>
     */
    protected static function localityLevels(): array
    {
        return ['country', 'state', 'district'];
    }
}
