<?php

declare(strict_types=1);

namespace Hasyirin\Address\Models;

use Hasyirin\Address\Concerns\InteractsWithCodeScope;
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
class State extends Model
{
    use InteractsWithCodeScope;
    use SoftDeletes;

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
        return $this->belongsTo(config('address.models.state'));
    }

    public function districts(): HasMany
    {
        return $this->hasMany(config('address.models.district'));
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(config('address.models.address'));
    }
}
