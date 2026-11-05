<?php

declare(strict_types=1);

namespace Hasyirin\Address\Models;

use Hasyirin\Address\Database\Factories\PostOfficeFactory;
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
 * @property string $name
 * @property State $state
 * @property Collection<int, Postcode> $postcodes
 */
class PostOffice extends Model
{
    /** @use HasFactory<PostOfficeFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): PostOfficeFactory
    {
        return PostOfficeFactory::new();
    }

    protected $fillable = [
        'state_id',
        'name',
    ];

    public function getTable(): string
    {
        return $this->table ?? config()->string('address.tables.post_offices', parent::getTable());
    }

    /** @return BelongsTo<State, $this> */
    public function state(): BelongsTo
    {
        /** @var class-string<State> $model */
        $model = config('address.models.state');

        return $this->belongsTo($model);
    }

    /** @return HasMany<Postcode, $this> */
    public function postcodes(): HasMany
    {
        /** @var class-string<Postcode> $model */
        $model = config('address.models.postcode');

        return $this->hasMany($model, 'post_office_id');
    }

    /** @param Builder<PostOffice> $query */
    public function scopeLocal(Builder $query): void
    {
        $query->whereHas('state', fn (Builder $state) => $state->local());
    }
}
