<?php

declare(strict_types=1);

namespace Hasyirin\Address\Models;

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
 * @property bool $local
 * @property Country $country
 * @property Collection $districts
 * @property Collection $addresses
 */
class State extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'country_id',
        'code',
        'name',
        'local',
    ];

    protected $attributes = [
        'local' => false,
    ];

    protected function casts(): array
    {
        return [
            'local' => 'boolean',
        ];
    }

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

    public static function local(): static
    {
        return static::query()->firstWhere('local', true);
    }
}
