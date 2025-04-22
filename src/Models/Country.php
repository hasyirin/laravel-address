<?php

declare(strict_types=1);

namespace Hasyirin\Address\Models;

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
 * @property boolean $local
 * @property Collection $states
 * @property Collection $districts
 * @property Collection $postOffices
 * @property Collection $addresses
 */
class Country extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'alpha_2',
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

    public static function local(): static
    {
        return static::query()->firstWhere('local', true);
    }
}
