<?php

declare(strict_types=1);

namespace Hasyirin\Address\Models;

use Hasyirin\Address\Database\Factories\PostcodeFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $post_office_id
 * @property string $code
 * @property ?float $latitude
 * @property ?float $longitude
 * @property PostOffice $postOffice
 */
class Postcode extends Model
{
    /** @use HasFactory<PostcodeFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): PostcodeFactory
    {
        return PostcodeFactory::new();
    }

    protected $fillable = [
        'post_office_id',
        'code',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function getTable(): string
    {
        return $this->table ?? config('address.tables.postcodes', parent::getTable());
    }

    public function postOffice(): BelongsTo
    {
        return $this->belongsTo(config('address.models.post-office'));
    }

    protected function code(): Attribute
    {
        return Attribute::set(fn (string $value) => (string) str($value)->squish()->upper());
    }
}
