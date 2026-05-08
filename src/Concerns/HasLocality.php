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
        /** @var ?static */
        return cache()->memo()->rememberForever(
            static::localCacheKey(),
            fn () => static::query()->local()->first(),
        );
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
