<?php

declare(strict_types=1);

namespace Hasyirin\Address\Models;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use UnitEnum;

class Address extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'addressable_type',
        'addressable_id',
        'country_id',
        'state_id',
        'types',
        'line_one',
        'line_two',
        'line_three',
        'postcode',
        'latitude',
        'longitude',
        'properties',
    ];

    protected $attributes = [
        'types' => '[]',
        'properties' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'types' => 'array',
            'properties' => 'array',
        ];
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('address.tables.addresses', parent::getTable());
    }

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(config('address.models.country'));
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(config('address.models.state'));
    }

    public function postOffice(): BelongsTo
    {
        return $this->belongsTo(config('address.models.post-office'));
    }

    public function scopeOfType(Builder $query, Arrayable|BackedEnum|UnitEnum|array|string $types): void
    {
        $query->whereIn('types', collect($types));
    }

    public function formatted(bool $country = true, bool $capitalize = false): string
    {
        $address = collect([
            $this->line_one,
            $this->line_two,
            $this->line_three,
            $this->postcode,
            $this->state->name,
            $country ? $this->country->name : null,
        ])->filter()
            ->map(fn (string $value) => (string) str($value)->rtrim(',')->trim())
            ->join(', ');

        if ($capitalize) {
            $address = strtoupper($address);
        }

        return $address;
    }
}
