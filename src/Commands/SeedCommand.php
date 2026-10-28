<?php

namespace Hasyirin\Address\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\View\Components\TwoColumnDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use JsonException;
use RuntimeException;

class SeedCommand extends Command
{
    public string $path {
        get => is_file(base_path('data/countries.json')) ? base_path('data') : __DIR__.'/../../data';
    }

    public $signature = 'address:seed';

    public $description = 'Seed countries and its relation for references.';

    public function handle(): int
    {
        $this->countries();

        return self::SUCCESS;
    }

    protected function countries(): void
    {
        $this->comment('Seeding countries...');

        $countries = $this->read('countries.json');

        $total = $countries->count();

        $countries->each(function (array $attributes, int $index) use ($total) {
            $country = $this->updateOrCreate(config('address.models.country'), ['code' => $attributes['code']], $attributes);

            $current = $index + 1;

            new TwoColumnDetail($this->output)->render($country['name'], "$current/$total");

            $states = $this->seed('states', ["countries/{$attributes['code']}" => $country], config('address.models.state'), 'country_id', 'code');

            $this->seed('post-offices', $states, config('address.models.post-office'), 'state_id', 'name');

            $districts = $this->seed('districts', $states, config('address.models.district'), 'state_id', 'code');

            $this->seed('subdistricts', $districts, config('address.models.subdistrict'), 'district_id', 'code');
        });

        $this->comment('Done!');
    }

    /**
     * @param  array<string, Model>  $parents
     * @return array<string, Model>
     */
    protected function seed(string $name, array $parents, string $model, string $foreignKey, string $key): array
    {
        $files = collect($parents)
            ->map(fn (Model $parent, string $directory) => $this->read("$directory/$name.json"))
            ->filter(fn (Collection $rows) => $rows->isNotEmpty());

        if ($files->isEmpty()) {
            return [];
        }

        $this->comment('Seeding '.str_replace('-', ' ', $name).'...');

        $progress = $this->output->createProgressBar($files->flatten(1)->count());
        $progress->start();

        $seeded = [];

        foreach ($files as $directory => $rows) {
            foreach ($rows as $attributes) {
                $seeded["$directory/$name/{$attributes[$key]}"] = $this->updateOrCreate(
                    $model,
                    [$foreignKey => $parents[$directory]->getKey(), $key => $attributes[$key]],
                    $attributes,
                );

                $progress->advance();
            }
        }

        $progress->finish();
        $this->newLine();

        return $seeded;
    }

    /**
     * @param  array<string, mixed>  $keys
     * @param  array<string, mixed>  $attributes
     */
    protected function updateOrCreate(string $model, array $keys, array $attributes): Model
    {
        $query = $model::withTrashed();

        return $query
            ->orderByRaw("case when {$query->getModel()->getDeletedAtColumn()} is null then 0 else 1 end")
            ->updateOrCreate($keys, $attributes);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function read(string $file): Collection
    {
        $path = "$this->path/$file";

        if (! is_file($path) || trim($json = file_get_contents($path)) === '') {
            return collect();
        }

        try {
            return collect(json_decode($json, true, flags: JSON_THROW_ON_ERROR));
        } catch (JsonException $e) {
            throw new RuntimeException("Invalid JSON in [$path]: {$e->getMessage()}", previous: $e);
        }
    }
}
