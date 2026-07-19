<?php

declare(strict_types=1);

namespace Hasyirin\Address\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasLocality
{
    abstract public function scopeLocal(Builder $query): void;

    public static function bootHasLocality(): void
    {
        $clear = static fn () => static::clearLocalCache();

        static::saved($clear);
        static::deleted($clear);
    }

    public static function local(): ?static
    {
        $query = static::query();

        // Cache the row's raw attributes, never the model instance: a persistent
        // store (e.g. redis) serializes a cached Eloquent model and hands it back
        // as a __PHP_Incomplete_Class on later requests. Rehydrate from the snapshot.
        /** @var array<string, mixed>|null $attributes */
        $attributes = cache()->memo()->rememberForever(
            static::localCacheKey(),
            fn () => $query->local()->first()?->getAttributes(),
        );

        if ($attributes === null) {
            return null;
        }

        // getModel() is the builder's own template instance, never routed through
        // newModelInstance(), so getConnectionName() on it is unset. getConnection()
        // (unlike getConnectionName()) resolves the app's actual default connection
        // instead of returning null, matching what a normally-queried instance reports.
        $model = $query->getModel();

        return $model->newFromBuilder($attributes, $model->getConnection()->getName());
    }

    public static function clearLocalCache(): void
    {
        cache()->memo()->forget(static::localCacheKey());

        foreach (static::localityDescendants() as $descendant) {
            $descendant::clearLocalCache();
        }
    }

    /**
     * @return array<int, class-string>
     */
    protected static function localityDescendants(): array
    {
        return [];
    }

    protected static function localCacheKey(): string
    {
        return 'address.locality.'.static::class;
    }
}
