<?php

declare(strict_types=1);

namespace Hasyirin\Address\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $state_id
 * @property string $name
 * @property array $postcodes
 * @property State $state
 */
class PostOffice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'state_id',
        'name',
        'postcodes',
    ];

    protected $attributes = [
        'postcodes' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'postcodes' => 'array',
        ];
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('address.tables.post_offices', parent::getTable());
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(config('address.models.state'));
    }
}
