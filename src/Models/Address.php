<?php

namespace Hasyirin\Address\Models;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use UnitEnum;

/**
 * @property int $id
 * @property string $addressable_type
 * @property int $addressable_id
 * @property int $post_office_id
 * @property int $country_id
 * @property int $state_id
 * @property string $type
 * @property string $line_1
 * @property string $line_2
 * @property string $line_3
 * @property string $postcode
 * @property float $latitude
 * @property float $longitude
 * @property array $properties
 * @property Model $addressable
 * @property ?Country $country
 * @property ?State $state
 * @property ?PostOffice $postOffice
 */
class Address extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'addressable_type',
        'addressable_id',
        'post_office_id',
        'country_id',
        'state_id',
        'type',
        'line_1',
        'line_2',
        'line_3',
        'postcode',
        'latitude',
        'longitude',
        'properties',
    ];

    protected $attributes = [
        'properties' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('address.tables.addresses', parent::getTable());
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $address) {
            $address->line_1 = str($address->line_1)->squish()->rtrim(',');
            $address->line_2 = str($address->line_2)->squish()->rtrim(',');
            $address->line_3 = str($address->line_3)->squish()->rtrim(',');
        });
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

    public function scopeOfType(Builder $query, Arrayable|BackedEnum|UnitEnum|array|string $type): void
    {
        $query->whereIn('type', collect($type));
    }

    public function formatted(bool $state = true, bool $country = true, bool $capitalize = false): string
    {
        $address = collect([
            $this->line_1,
            $this->line_2,
            $this->line_3,
            $this->postcode,
            $this->postOffice?->name,
            $state ? $this->state?->name : null,
            $country ? $this->country?->name : null,
        ])->filter()
            ->map(fn (string $value) => (string) str($value)->rtrim(',')->trim())
            ->join(', ');

        if ($capitalize) {
            $address = strtoupper($address);
        }

        return $address;
    }

    public function render(bool $inline = false, bool $state = true, bool $country = true, bool $capitalize = false, int $margin = 0): string
    {
        $address = collect([
            [$this->line_1],
            [$this->line_2],
            [$this->line_3],
            [$this->postcode, $this->postOffice?->name],
            $state ? [$this->state?->name] : [],
            $country ? [$this->country?->name] : [],
        ])->map(fn (array $line) => collect($line)
            ->filter()
            ->map(fn ($line) => str($line)->rtrim(',')->trim()->when($capitalize, fn ($line) => strtoupper($line)))
            ->filter()
            ->join(', ')
        )->filter()
            ->map(fn (string $line) => ($inline) ? "$line," : "<p class=\"mb-{$margin}\">$line,</p>")
            ->join("\n");

        return ($inline)
            ? str($address)->rtrim(',')
            : rtrim($address, ',</p>').'</p>';
    }

    public function copy(): self
    {
        return self::make($this->only([
            'post_office_id',
            'country_id',
            'state_id',
            'line_1',
            'line_2',
            'line_3',
            'postcode',
            'latitude',
            'longitude',
            'properties',
        ]));
    }
}
