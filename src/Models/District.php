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
 * @property Collection<Subdistrict> $subdistricts
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

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('address.tables.districts', parent::getTable());
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(config('address.models.state'));
    }

    public function subdistricts(): HasMany
    {
        return $this->hasMany(config('address.models.subdistrict'));
    }

    public function scopeLocal(Builder $query): void
    {
        $query->where('code', config('address.locality.district'))
            ->whereHas('state', function (Builder $q) {
                $q->where('code', config('address.locality.state'))
                    ->whereHas('country', fn (Builder $r) => $r->where('code', config('address.locality.country')));
            });
    }
}
