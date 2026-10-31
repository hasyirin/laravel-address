<?php

declare(strict_types=1);

namespace Hasyirin\Address\Concerns;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasLocality
{
    abstract public function scopeLocal(Builder $query): void;

    /**
     * @return array<int, string>
     */
    abstract protected static function localityLevels(): array;

    public static function bootHasLocality(): void
    {
        $clear = static fn () => static::clearLocalCache();

        $clearAfterWrite = static function (Model $model) use ($clear) {
            $clear();

            $model->getConnection()->afterRollBack($clear);
        };

        static::saving($clear);
        static::created($clearAfterWrite);
        static::updated($clearAfterWrite);
        static::deleting($clear);
        static::deleted($clearAfterWrite);
    }

    public static function local(): ?static
    {
        if (in_array(null, static::localityCodes(), true)) {
            return null;
        }

        $query = static::query();

        // getModel() is the builder's own template instance, never routed through
        // newModelInstance(), so getConnectionName() on it is unset. getConnection()
        // (unlike getConnectionName()) resolves the app's actual default connection
        // instead of returning null, matching what a normally-queried instance reports.
        $model = $query->getModel();
        $connection = $model->getConnection();

        $memo = app('address.locality');
        $key = static::localityMemoKey($connection);

        // The memo stores attributes so that callers never share one model instance.
        if (! isset($memo[$key])) {
            $attributes = $query->local()->first()?->getAttributes();

            // Never cache a miss: rows inserted without model events would stay hidden.
            if ($attributes === null) {
                return null;
            }

            $memo[$key] = $attributes;
        }

        return $model->newFromBuilder($memo[$key], $connection->getName());
    }

    public static function clearLocalCache(): void
    {
        app('address.locality')->exchangeArray([]);
    }

    protected static function localityCode(string $level): ?string
    {
        $code = config("address.locality.$level");

        return $code === null ? null : (string) $code;
    }

    /**
     * @return array<string, ?string>
     */
    protected static function localityCodes(): array
    {
        $levels = static::localityLevels();

        return array_combine($levels, array_map(static::localityCode(...), $levels));
    }

    protected static function localityMemoKey(Connection $connection): string
    {
        return json_encode([
            static::localityCodes(),
            $connection->getName(),
            $connection->getDriverName(),
            $connection->getConfig('host'),
            $connection->getConfig('unix_socket'),
            $connection->getConfig('port'),
            $connection->getDatabaseName(),
            $connection->getTablePrefix(),
            $connection->getConfig('search_path'),
        ]);
    }
}
