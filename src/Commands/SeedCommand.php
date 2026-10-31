<?php

namespace Hasyirin\Address\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use JsonException;
use RuntimeException;

class SeedCommand extends Command
{
    public string $path {
        get => is_file(database_path('data/address/countries.json')) ? database_path('data/address') : __DIR__.'/../../data';
    }

    public $signature = 'address:seed';

    public $description = 'Seed countries and its relation for references.';

    public function handle(): int
    {
        $country = config('address.models.country');

        new $country()->getConnection()->transaction(function () use ($country) {
            $countries = $this->seed($this->rows(['' => null], 'countries', 'code'), $country, null, 'code', ['name', 'alpha_2']);

            $states = $this->seed($this->rows($countries, 'states', 'code'), config('address.models.state'), 'country_id', 'code', ['name']);

            $postOffices = $this->rows($states, 'post-offices', 'name');

            $postOfficeIds = $this->seed($postOffices, config('address.models.post-office'), 'state_id', 'name', []);

            $this->seed($this->postcodes($postOffices, $postOfficeIds), config('address.models.postcode'), 'post_office_id', 'code', [], hasChildren: false);

            $districts = $this->seed($this->rows($states, 'districts', 'code'), config('address.models.district'), 'state_id', 'code', ['name']);

            $this->seed($this->rows($districts, 'subdistricts', 'code'), config('address.models.subdistrict'), 'district_id', 'code', ['name'], hasChildren: false);
        });

        $country::clearLocalCache();

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array{int|null, array<string, mixed>}>  $rows
     * @param  array<int, string>  $columns
     * @return array<string, int>
     */
    protected function seed(array $rows, string $model, ?string $foreignKey, string $key, array $columns, bool $hasChildren = true): array
    {
        if ($rows === []) {
            return [];
        }

        $instance = new $model;
        $uniqueBy = array_values(array_filter([$foreignKey, $key]));

        $attributes = $data = $uniqueKeys = [];

        foreach ($rows as $path => [$parentId, $row]) {
            $values = [...array_fill_keys($columns, null), ...Arr::only($row, $columns)];

            $built = $instance->newInstance()
                ->forceFill([...($foreignKey === null ? [] : [$foreignKey => $parentId]), $key => $row[$key], ...$values])
                ->getAttributes();

            $unique = $this->uniqueKey($built, $uniqueBy);

            $uniqueKeys[$path] = $unique;
            $attributes[$unique] = $built;
            $data[$unique] = $values;
        }

        $read = fn () => $this->existing($model, $foreignKey ?? $key, array_column($attributes, $foreignKey ?? $key))
            ->keyBy(fn (Model $existing) => $this->uniqueKey($existing->getAttributes(), $uniqueBy));

        $existing = $read();

        $writes = [];
        $inserts = false;

        foreach ($attributes as $unique => $built) {
            $current = $existing->get($unique);

            $inserts = $inserts || $current === null;

            if ($current === null || $current->forceFill($data[$unique])->isDirty()) {
                $writes[] = $built;
            }
        }

        // SQL Server allows 2,100 parameters per statement.
        foreach (array_chunk($writes, 200) as $chunk) {
            // MySQL and MariaDB also match a key that differs only in case, so the key is updated too.
            $model::query()->withoutGlobalScopes()->upsert($chunk, $uniqueBy, [$key, ...$columns]);
        }

        $this->components->twoColumnDetail($instance->getTable(), count($rows).' read, '.count($writes).' written');

        if (! $hasChildren) {
            return [];
        }

        if ($inserts) {
            $existing = $read();
        }

        $ids = [];

        foreach ($uniqueKeys as $path => $unique) {
            $ids[$path] = $existing->get($unique)?->getKey()
                ?? throw new RuntimeException("Found no row for [$path] after seeding. Its key may differ from another row's key only in case, accents, or trailing spaces.");
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $uniqueBy
     */
    protected function uniqueKey(array $attributes, array $uniqueBy): string
    {
        return implode("\0", array_map(fn (string $column) => $attributes[$column], $uniqueBy));
    }

    /**
     * @param  array<int, mixed>  $values
     * @return Collection<int, Model>
     */
    protected function existing(string $model, string $column, array $values): Collection
    {
        return collect(array_chunk(array_values(array_unique($values)), 1000))
            ->flatMap(fn (array $chunk) => $model::query()->withoutGlobalScopes()->whereIn($column, $chunk)->get());
    }

    /**
     * @param  array<string, int|null>  $parents
     * @return array<string, array{int|null, array<string, mixed>}>
     */
    protected function rows(array $parents, string $name, string $key): array
    {
        $rows = [];

        foreach ($parents as $path => $parentId) {
            foreach ($this->read(ltrim("$path/$name.json", '/'), $key) as $row) {
                $rows[ltrim("$path/$name/{$row[$key]}", '/')] = [$parentId, $row];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, array{int|null, array<string, mixed>}>  $postOffices
     * @param  array<string, int>  $ids
     * @return array<string, array{int, array{code: string}}>
     */
    protected function postcodes(array $postOffices, array $ids): array
    {
        $rows = [];

        foreach ($postOffices as $path => [, $postOffice]) {
            foreach ($postOffice['postcodes'] ?? [] as $code) {
                $rows["$path/postcodes/$code"] = [$ids[$path], ['code' => $code]];
            }
        }

        return $rows;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function read(string $file, string $key): Collection
    {
        $path = "$this->path/$file";

        if (! is_file($path) || trim($json = file_get_contents($path)) === '') {
            return collect();
        }

        try {
            $rows = collect(json_decode($json, true, flags: JSON_THROW_ON_ERROR));
        } catch (JsonException $e) {
            throw new RuntimeException("Invalid JSON in [$path]: {$e->getMessage()}", previous: $e);
        }

        return $rows->each(function (array $row) use ($key, $path) {
            if (blank($row[$key] ?? null)) {
                throw new RuntimeException("Missing [$key] in [$path]");
            }
        });
    }
}
