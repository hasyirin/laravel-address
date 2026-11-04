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
use InvalidArgumentException;
use UnitEnum;

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
 * @property array<string, mixed> $properties
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
        return $this->table ?? config()->string('address.tables.addresses', parent::getTable());
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

    /** @return MorphTo<Model, $this> */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        /** @var class-string<Country> $model */
        $model = config('address.models.country');

        return $this->belongsTo($model)->withoutGlobalScope(SoftDeletingScope::class);
    }

    /** @return BelongsTo<State, $this> */
    public function state(): BelongsTo
    {
        /** @var class-string<State> $model */
        $model = config('address.models.state');

        return $this->belongsTo($model)->withoutGlobalScope(SoftDeletingScope::class);
    }

    /** @return BelongsTo<District, $this> */
    public function district(): BelongsTo
    {
        /** @var class-string<District> $model */
        $model = config('address.models.district');

        return $this->belongsTo($model)->withoutGlobalScope(SoftDeletingScope::class);
    }

    /** @return BelongsTo<Subdistrict, $this> */
    public function subdistrict(): BelongsTo
    {
        /** @var class-string<Subdistrict> $model */
        $model = config('address.models.subdistrict');

        return $this->belongsTo($model)->withoutGlobalScope(SoftDeletingScope::class);
    }

    /** @return BelongsTo<PostOffice, $this> */
    public function postOffice(): BelongsTo
    {
        /** @var class-string<PostOffice> $model */
        $model = config('address.models.post-office');

        return $this->belongsTo($model)->withoutGlobalScope(SoftDeletingScope::class);
    }

    /**
     * @param  Builder<static>  $query
     * @param  Arrayable<array-key, mixed>|array<array-key, mixed>|string|UnitEnum  $type
     */
    public function scopeOfType(Builder $query, Arrayable|BackedEnum|UnitEnum|array|string $type): void
    {
        $types = match (true) {
            is_string($type), $type instanceof UnitEnum => [$type],
            is_array($type) => $type,
            default => $type->toArray(),
        };

        $query->whereIn($query->qualifyColumn('type'), array_map(fn (mixed $value) => match (true) {
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof UnitEnum => $value->name,
            is_scalar($value) => (string) $value,
            default => throw new InvalidArgumentException('An address type must be a string, a number, or an enum.'),
        }, $types));
    }

    /**
     * @return list<string>
     */
    public function lines(bool $state = true, bool $country = true, bool $capitalize = false): array
    {
        $lines = self::filled([
            $this->line_1,
            $this->line_2,
            $this->line_3,
            implode(' ', self::filled([$this->postcode, $this->postOffice?->name])),
            $state ? $this->state?->name : null,
            $country ? $this->country?->name : null,
        ]);

        return $capitalize ? array_map(mb_strtoupper(...), $lines) : $lines;
    }

    /**
     * @param  list<?string>  $parts
     * @return list<string>
     */
    private static function filled(array $parts): array
    {
        $cleaned = array_map(fn (?string $part) => (string) str($part)->trim()->rtrim(', '), $parts);

        return array_values(array_filter($cleaned, fn (string $part) => $part !== ''));
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
