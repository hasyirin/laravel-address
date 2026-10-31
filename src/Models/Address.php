<?php

namespace Hasyirin\Address\Models;

use BackedEnum;
use Hasyirin\Address\Database\Factories\AddressFactory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Stringable;
use UnitEnum;

use function Illuminate\Support\enum_value;

/**
 * @property int $id
 * @property string $addressable_type
 * @property int $addressable_id
 * @property ?int $post_office_id
 * @property ?int $country_id
 * @property ?int $state_id
 * @property ?int $district_id
 * @property ?int $subdistrict_id
 * @property string $type
 * @property ?string $line_1
 * @property ?string $line_2
 * @property ?string $line_3
 * @property ?string $postcode
 * @property ?float $latitude
 * @property ?float $longitude
 * @property array $properties
 * @property Model $addressable
 * @property ?Country $country
 * @property ?State $state
 * @property ?District $district
 * @property ?Subdistrict $subdistrict
 * @property ?PostOffice $postOffice
 */
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }

    protected $fillable = [
        'post_office_id',
        'country_id',
        'state_id',
        'district_id',
        'subdistrict_id',
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
            'latitude' => 'float',
            'longitude' => 'float',
            'properties' => 'array',
        ];
    }

    public function getTable(): string
    {
        return $this->table ?? config('address.tables.addresses', parent::getTable());
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $address) {
            foreach (['line_1', 'line_2', 'line_3'] as $line) {
                if ($address->isDirty($line) && $address->{$line} !== null) {
                    $address->{$line} = static::blankToNull(str($address->{$line})->squish()->rtrim(', '));
                }
            }

            if ($address->isDirty('postcode') && $address->postcode !== null) {
                $address->postcode = static::blankToNull(str($address->postcode)->squish()->upper());
            }
        });
    }

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(config('address.models.country'))->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(config('address.models.state'))->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(config('address.models.district'))->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function subdistrict(): BelongsTo
    {
        return $this->belongsTo(config('address.models.subdistrict'))->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function postOffice(): BelongsTo
    {
        return $this->belongsTo(config('address.models.post-office'))->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function scopeOfType(Builder $query, Arrayable|BackedEnum|UnitEnum|array|string $type): void
    {
        $query->whereIn($query->qualifyColumn('type'), collect($type)->map(fn (mixed $value) => (string) enum_value($value)));
    }

    /**
     * @return list<string>
     */
    public function lines(bool $state = true, bool $country = true, bool $capitalize = false): array
    {
        $clean = fn (?string $value) => (string) str($value)->trim()->rtrim(', ');
        $filled = fn (array $parts) => array_values(array_filter(array_map($clean, $parts), fn (string $part) => $part !== ''));

        $lines = $filled([
            $this->line_1,
            $this->line_2,
            $this->line_3,
            implode(' ', $filled([$this->postcode, $this->postOffice?->name])),
            $state ? $this->state?->name : null,
            $country ? $this->country?->name : null,
        ]);

        return $capitalize ? array_map(mb_strtoupper(...), $lines) : $lines;
    }

    public function formatted(string $separator = ', ', bool $state = true, bool $country = true, bool $capitalize = false): string
    {
        return implode($separator, $this->lines($state, $country, $capitalize));
    }

    public function copy(): self
    {
        return $this->replicate(['addressable_type', 'addressable_id', 'type', $this->getDeletedAtColumn()])
            ->unsetRelation('addressable');
    }

    protected static function blankToNull(Stringable $value): ?string
    {
        return $value->isEmpty() ? null : $value->toString();
    }
}
