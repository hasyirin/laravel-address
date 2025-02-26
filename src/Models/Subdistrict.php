<?php

declare(strict_types=1);

namespace Hasyirin\Address\Models;

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
    use SoftDeletes;

    protected $fillable = [
        'district_id',
        'code',
        'name',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('address.tables.subdistricts', parent::getTable());
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(config('address.models.district'));
    }
}
