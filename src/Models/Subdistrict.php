<?php

declare(strict_types=1);

namespace Hasyirin\Address\Models;

use Hasyirin\Address\Database\Factories\SubdistrictFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $district_id
 * @property string $code
 * @property string $name
 * @property District $district
 */
class Subdistrict extends Model
{
    /** @use HasFactory<SubdistrictFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): SubdistrictFactory
    {
        return SubdistrictFactory::new();
    }

    protected $fillable = [
        'district_id',
        'code',
        'name',
    ];

    public function getTable(): string
    {
        return $this->table ?? config('address.tables.subdistricts', parent::getTable());
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(config('address.models.district'));
    }
}
