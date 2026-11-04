<?php

namespace Hasyirin\Address\Commands;

use ArrayObject;
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
        $country = $this->model('country');

        new $country()->getConnection()->transaction(function () use ($country) {
            $countries = $this->seed($this->rows(['' => null], 'countries', 'code'), $country, null, 'code', ['name', 'alpha_2']);

            $states = $this->seed($this->rows($countries, 'states', 'code'), $this->model('state'), 'country_id', 'code', ['name']);

            $postOffices = $this->rows($states, 'post-offices', 'name');

            $postOfficeIds = $this->seed($postOffices, $this->model('post-office'), 'state_id', 'name', []);

            $this->seed($this->postcodes($postOffices, $postOfficeIds), $this->model('postcode'), 'post_office_id', 'code', [], hasChildren: false);

            $districts = $this->seed($this->rows($states, 'districts', 'code'), $this->model('district'), 'state_id', 'code', ['name']);

            $this->seed($this->rows($districts, 'subdistricts', 'code'), $this->model('subdistrict'), 'district_id', 'code', ['name'], hasChildren: false);
        });

        /** @var ArrayObject<string, mixed> $locality */
        $locality = app('address.locality');
        $locality->exchangeArray([]);

        return self::SUCCESS;
    }

    /**
     * @return class-string<Model>
     */
    protected function model(string $name): string
    {
        $model = config("address.models.$name");

        if (! is_string($model) || ! is_a($model, Model::class, true)) {
            throw new RuntimeException("[address.models.$name] must name an Eloquent model.");
        }

        return $model;
    }

    /**
     * @param  array<string, array{int|string|null, array<string, mixed>}>  $rows
     * @param  class-string<Model>  $model
     * @param  array<int, string>  $columns
     * @return array<string, int|string>
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
            $id = $existing->get($unique)?->getKey();

            if (! is_int($id) && ! is_string($id)) {
                throw new RuntimeException("Found no row for [$path] after seeding. Its key may differ from another row's key only in case, accents, or trailing spaces.");
            }

            $ids[$path] = $id;
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $uniqueBy
     */
    protected function uniqueKey(array $attributes, array $uniqueBy): string
    {
        return implode("\0", array_map(fn (string $column) => is_scalar($value = $attributes[$column] ?? null) ? (string) $value : '', $uniqueBy));
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<int, mixed>  $values
     * @return Collection<int, Model>
     */
    protected function existing(string $model, string $column, array $values): Collection
    {
        $keys = array_unique(array_filter($values, fn (mixed $value) => is_int($value) || is_string($value)));

        return collect(array_chunk(array_values($keys), 1000))
            ->flatMap(fn (array $chunk) => $model::query()->withoutGlobalScopes()->whereIn($column, $chunk)->get())
            ->values();
    }

    /**
     * @param  array<string, int|string|null>  $parents
     * @return array<string, array{int|string|null, array<string, mixed>}>
     */
    protected function rows(array $parents, string $name, string $key): array
    {
        $rows = [];

        foreach ($parents as $path => $parentId) {
            foreach ($this->read(ltrim("$path/$name.json", '/'), $key) as $id => $row) {
                $rows[ltrim("$path/$name/$id", '/')] = [$parentId, $row];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, array{int|string|null, array<string, mixed>}>  $postOffices
     * @param  array<string, int|string>  $ids
     * @return array<string, array{int|string, array{code: int|string}}>
     */
    protected function postcodes(array $postOffices, array $ids): array
    {
        $rows = [];

        foreach ($postOffices as $path => [, $postOffice]) {
            $codes = $postOffice['postcodes'] ?? [];

            if (! is_array($codes) || array_filter($codes, fn (mixed $code) => ! $this->isKey($code)) !== []) {
                throw new RuntimeException("Invalid [postcodes] in [$path]");
            }

            /** @var array<int|string> $codes */
            foreach ($codes as $code) {
                $rows["$path/postcodes/$code"] = [$ids[$path], ['code' => $code]];
            }
        }

        return $rows;
    }

    /**
     * @return Collection<int|string, array<string, mixed>>
     */
    protected function read(string $file, string $key): Collection
    {
        $path = "$this->path/$file";
        $json = is_file($path) ? file_get_contents($path) : false;
        $decoded = [];

        if ($json !== false && trim($json) !== '') {
            try {
                $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new RuntimeException("Invalid JSON in [$path]: {$e->getMessage()}", previous: $e);
            }
        }

        $rows = [];

        foreach (is_array($decoded) ? $decoded : [$decoded] as $row) {
            if (! is_array($row) || ! $this->isKey($id = $row[$key] ?? null)) {
                throw new RuntimeException("Missing [$key] in [$path]");
            }

            /** @var array<string, mixed> $row */
            $rows[$id] = $row;
        }

        return collect($rows);
    }

    /**
     * @phpstan-assert-if-true int|string $value
     */
    protected function isKey(mixed $value): bool
    {
        return (is_string($value) || is_int($value)) && ! blank($value);
    }
}
