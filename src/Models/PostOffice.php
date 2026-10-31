<?php

declare(strict_types=1);

namespace Hasyirin\Address\Models;

use Hasyirin\Address\Database\Factories\PostOfficeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $state_id
 * @property string $name
 * @property State $state
 * @property Collection<Postcode> $postcodes
 */
class PostOffice extends Model
{
    /** @use HasFactory<PostOfficeFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): PostOfficeFactory
    {
        return PostOfficeFactory::new();
    }

    protected $fillable = [
        'state_id',
        'name',
    ];

    public function getTable(): string
    {
        return $this->table ?? config('address.tables.post_offices', parent::getTable());
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(config('address.models.state'));
    }

    public function postcodes(): HasMany
    {
        return $this->hasMany(config('address.models.postcode'), 'post_office_id');
    }
}
