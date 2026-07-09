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
        // Cache the row's raw attributes, never the model instance: a persistent
        // store (e.g. redis) serializes a cached Eloquent model and hands it back
        // as a __PHP_Incomplete_Class on later requests. Rehydrate from the snapshot.
        /** @var array<string, mixed>|null $attributes */
        $attributes = cache()->memo()->rememberForever(
            static::localCacheKey(),
            fn () => static::query()->local()->first()?->getAttributes(),
        );

        return $attributes === null
            ? null
            : static::query()->getModel()->newFromBuilder($attributes);
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
