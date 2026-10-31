<?php

use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\District;
use Hasyirin\Address\Models\Postcode;
use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;
use Hasyirin\Address\Models\Subdistrict;
use Hasyirin\Address\Tests\Fixtures\ActiveCountry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

function putAppData(array $files): void
{
    foreach ($files as $file => $contents) {
        File::ensureDirectoryExists(dirname(database_path("data/address/$file")));
        File::put(database_path("data/address/$file"), $contents);
    }
}

beforeEach(function () {
    File::deleteDirectory(database_path('data/address'));
});

afterEach(function () {
    File::deleteDirectory(database_path('data/address'));
});

it('runs the seed command successfully', function () {
    $this->artisan('address:seed')
        ->assertSuccessful();
});

it('seeds countries from data file', function () {
    $this->artisan('address:seed');

    $countriesData = json_decode(
        file_get_contents(__DIR__.'/../../data/countries.json'),
        true
    );

    expect(Country::count())->toBe(count($countriesData));
});

it('seeds every level from the data files', function () {
    $this->artisan('address:seed')->assertSuccessful();

    $rows = fn (string $pattern) => collect(glob(__DIR__."/../../data/countries/$pattern"))
        ->sum(fn (string $file) => count(json_decode(file_get_contents($file), true) ?? []));

    $postcodes = collect(glob(__DIR__.'/../../data/countries/*/states/*/post-offices.json'))
        ->flatMap(fn (string $file) => json_decode(file_get_contents($file), true))
        ->sum(fn (array $postOffice) => count($postOffice['postcodes']));

    expect(State::count())->toBe($rows('*/states.json'))
        ->and(Postcode::count())->toBe($postcodes)
        ->and(PostOffice::count())->toBe($rows('*/states/*/post-offices.json'))
        ->and(District::count())->toBe($rows('*/states/*/districts.json'))
        ->and(Subdistrict::count())->toBe($rows('*/states/*/districts/*/subdistricts.json'));
});

it('links seeded rows to their parents', function () {
    $this->artisan('address:seed')->assertSuccessful();

    $district = District::where('name', 'Jelebu')->sole();
    $postOffice = PostOffice::where('name', 'Bahau')->sole();
    $subdistrict = Subdistrict::where('name', 'Gelami Lemi')->sole();

    expect($district->state->name)->toBe('Negeri Sembilan')
        ->and($district->state->country->code)->toBe('MYS')
        ->and($postOffice->state->name)->toBe('Negeri Sembilan')
        ->and($postOffice->postcodes->pluck('code')->sort()->values()->all())->toBe(['72100', '72107', '72109'])
        ->and($subdistrict->district->is($district))->toBeTrue();
});

it('seeds when models are strict', function () {
    Model::shouldBeStrict();

    try {
        $this->artisan('address:seed')->assertSuccessful();
    } finally {
        Model::shouldBeStrict(false);
    }
});

it('keeps a soft-deleted country deleted when seeding again', function () {
    $this->artisan('address:seed')->assertSuccessful();

    Country::where('code', 'AFG')->sole()->delete();

    $this->artisan('address:seed')->assertSuccessful();

    expect(Country::withTrashed()->where('code', 'AFG')->sole()->trashed())->toBeTrue();
});

it('does not duplicate a soft-deleted post office when seeding again', function () {
    $this->artisan('address:seed')->assertSuccessful();

    PostOffice::where('name', 'Bahau')->sole()->delete();

    $this->artisan('address:seed')->assertSuccessful();

    expect(PostOffice::withTrashed()->where('name', 'Bahau')->sole()->trashed())->toBeTrue();
});

it('seeds on a connection with a table prefix', function () {
    config([
        'database.connections.prefixed' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'app_'],
        'database.default' => 'prefixed',
    ]);

    foreach (['create_countries_table', 'create_states_table', 'create_post_offices_table', 'create_postcodes_table', 'create_districts_table', 'create_subdistricts_table'] as $migration) {
        (include __DIR__."/../../database/migrations/{$migration}.php.stub")->up();
    }

    $this->artisan('address:seed')->assertSuccessful();

    expect(PostOffice::count())->toBeGreaterThan(0);
});

it('treats an empty data file as having no rows', function () {
    putAppData([
        'countries.json' => json_encode([['code' => 'XXA', 'name' => 'Testland', 'alpha_2' => 'XA']]),
        'countries/XXA/states.json' => '',
    ]);

    $this->artisan('address:seed')->assertSuccessful();

    expect(Country::pluck('code')->all())->toBe(['XXA'])
        ->and(State::count())->toBe(0);
});

it('seeds from the app data directory when it has countries.json', function () {
    putAppData(['countries.json' => json_encode([['code' => 'XXA', 'name' => 'Testland', 'alpha_2' => 'XA']])]);

    $this->artisan('address:seed')->assertSuccessful();

    expect(Country::pluck('code')->all())->toBe(['XXA']);
});

it('falls back to the package data when the app data directory has no countries.json', function () {
    File::ensureDirectoryExists(database_path('data/address'));

    $this->artisan('address:seed')->assertSuccessful();

    expect(Country::where('code', 'MYS')->exists())->toBeTrue();
});

it('reports the file that holds invalid json', function () {
    putAppData(['countries.json' => '[{"code": "XXA",']);

    expect(fn () => $this->artisan('address:seed')->run())
        ->toThrow(RuntimeException::class, database_path('data/address/countries.json'));
});

it('resolves the local country from locality config after seeding', function () {
    config()->set('address.locality.country', 'MYS');

    $this->artisan('address:seed');

    $local = Country::local();

    expect($local)->not->toBeNull()
        ->and($local->code)->toBe('MYS');
});

it('seeds every level in 33 queries', function () {
    DB::enableQueryLog();

    $this->artisan('address:seed')->assertSuccessful();

    expect(DB::getQueryLog())->toHaveCount(33);
});

it('reads 6 times and writes nothing when the data did not change', function () {
    $this->artisan('address:seed')->assertSuccessful();
    DB::enableQueryLog();

    $this->artisan('address:seed')->assertSuccessful();

    expect(collect(DB::getQueryLog())->pluck('query'))->toHaveCount(6)
        ->each->toStartWith('select');
});

it('keeps updated_at when the data did not change', function () {
    $this->artisan('address:seed')->assertSuccessful();
    $before = DB::table('countries')->where('code', 'MYS')->value('updated_at');

    $this->travel(1)->hours();
    $this->artisan('address:seed')->assertSuccessful();

    expect(DB::table('countries')->where('code', 'MYS')->value('updated_at'))->toBe($before);
});

it('writes only the row that changed', function () {
    putAppData(['countries.json' => json_encode([['code' => 'XXA', 'name' => 'Testland', 'alpha_2' => 'XA'], ['code' => 'XXB', 'name' => 'Otherland', 'alpha_2' => 'XB']])]);
    $this->artisan('address:seed')->assertSuccessful();
    putAppData(['countries.json' => json_encode([['code' => 'XXA', 'name' => 'Testland', 'alpha_2' => 'XA'], ['code' => 'XXB', 'name' => 'New Otherland', 'alpha_2' => 'XB']])]);
    DB::enableQueryLog();

    $this->artisan('address:seed')->assertSuccessful();

    expect(collect(DB::getQueryLog())->pluck('query')->reject(fn (string $sql) => str_starts_with($sql, 'select')))->toHaveCount(1)
        ->and(Country::query()->where('code', 'XXB')->value('name'))->toBe('New Otherland');
});

it('uses no ids when the data did not change', function () {
    $this->artisan('address:seed')->assertSuccessful();
    $first = Country::factory()->create()->id;

    $this->artisan('address:seed')->assertSuccessful();

    expect(Country::factory()->create()->id)->toBe($first + 1);
});

it('writes nothing when a later file is invalid', function () {
    putAppData([
        'countries.json' => json_encode([['code' => 'XXA', 'name' => 'Testland', 'alpha_2' => 'XA']]),
        'countries/XXA/states.json' => '[{"code": "01",',
    ]);

    expect(fn () => $this->artisan('address:seed')->run())->toThrow(RuntimeException::class)
        ->and(Country::withTrashed()->count())->toBe(0);
});

it('does not use the cache store', function () {
    config(['cache.default' => 'database']);

    $this->artisan('address:seed')->assertSuccessful();

    expect(Country::query()->count())->toBe(249);
});

it('ignores unknown keys in a data file', function () {
    putAppData(['countries.json' => json_encode([['id' => 999, 'code' => 'XXA', 'name' => 'Testland', 'alpha_2' => 'XA', 'flag' => 'none']])]);

    $this->artisan('address:seed')->assertSuccessful();

    expect(Country::sole())->code->toBe('XXA')->id->not->toBe(999);
});

it('seeds through the configured model classes', function () {
    Schema::table('countries', fn (Blueprint $table) => $table->boolean('is_active')->nullable());
    config(['address.models.country' => ActiveCountry::class]);

    $this->artisan('address:seed')->assertSuccessful();

    expect(ActiveCountry::query()->where('code', 'MYS')->value('is_active'))->toBeTruthy();
});

it('has no post office names in one state that MySQL treats as equal', function () {
    collect(glob(__DIR__.'/../../data/countries/*/states/*/post-offices.json'))
        ->each(fn (string $file) => expect(collect(json_decode(file_get_contents($file), true))
            ->map(fn (array $row) => rtrim(mb_strtolower(Str::ascii($row['name']))))
            ->duplicates())->toBeEmpty());
});

it('clears a cached locality after seeding', function () {
    config(['address.locality.country' => 'MYS', 'address.locality.state' => '05']);
    $mys = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    State::create(['country_id' => $mys->id, 'code' => '05', 'name' => 'Old Name']);

    expect(State::local()?->name)->toBe('Old Name');

    $this->artisan('address:seed')->assertSuccessful();

    expect(State::local()?->name)->toBe('Negeri Sembilan');
});

it('keeps an app column when seeding again', function () {
    Schema::table('countries', fn (Blueprint $table) => $table->boolean('is_active')->default(true));
    config(['address.models.country' => ActiveCountry::class]);
    $this->artisan('address:seed')->assertSuccessful();
    ActiveCountry::query()->where('code', 'MYS')->update(['is_active' => false]);

    $this->artisan('address:seed')->assertSuccessful();

    expect(ActiveCountry::query()->where('code', 'MYS')->value('is_active'))->toBeFalsy();
});

it('seeds a row that leaves out an optional column', function () {
    putAppData(['countries.json' => json_encode([
        ['code' => 'XXA', 'name' => 'Testland', 'alpha_2' => 'XA'],
        ['code' => 'XXB', 'name' => 'Otherland'],
    ])]);

    $this->artisan('address:seed')->assertSuccessful();

    expect(Country::query()->where('code', 'XXB')->first())
        ->name->toBe('Otherland')
        ->alpha_2->toBeNull();
});

it('keeps the last of two rows with the same key', function () {
    putAppData([
        'countries.json' => json_encode([['code' => 'XXA', 'name' => 'Testland', 'alpha_2' => 'XA']]),
        'countries/XXA/states.json' => json_encode([['code' => '01', 'name' => 'First'], ['code' => '01', 'name' => 'Second']]),
    ]);

    $this->artisan('address:seed')->assertSuccessful();

    expect(State::sole()->name)->toBe('Second');
});

it('binds at most 2,100 parameters per query', function () {
    $bindings = [];
    DB::listen(function (QueryExecuted $query) use (&$bindings) {
        $bindings[] = count($query->bindings);
    });

    $this->artisan('address:seed')->assertSuccessful();

    expect(max($bindings))->toBeLessThanOrEqual(2100);
});

it('reads at most 1,000 values per query', function () {
    putAppData(['countries.json' => json_encode(collect(range(1, 1001))->map(fn (int $number) => [
        'code' => sprintf('%03s', base_convert((string) $number, 10, 36)),
        'name' => "Land $number",
        'alpha_2' => 'XA',
    ])->all())]);
    $bindings = [];
    DB::listen(function (QueryExecuted $query) use (&$bindings) {
        if (str_starts_with($query->sql, 'select')) {
            $bindings[] = count($query->bindings);
        }
    });

    $this->artisan('address:seed')->assertSuccessful();

    expect(max($bindings))->toBeLessThanOrEqual(1000);
});

it('names the file when a row has no key', function () {
    putAppData(['countries.json' => json_encode([['name' => 'Testland', 'alpha_2' => 'XA']])]);

    expect(fn () => $this->artisan('address:seed')->run())
        ->toThrow(RuntimeException::class, 'Missing [code] in ['.database_path('data/address/countries.json').']');
});

it('prints the rows read and written per level', function () {
    $this->artisan('address:seed')->expectsOutputToContain('249 read, 249 written')->assertSuccessful();

    $this->artisan('address:seed')->expectsOutputToContain('249 read, 0 written')->assertSuccessful();
});

it('seeds again when a key changes only in case', function () {
    putAppData([
        'countries.json' => json_encode([['code' => 'XXA', 'name' => 'Testland', 'alpha_2' => 'XA']]),
        'countries/XXA/states.json' => json_encode([['code' => 'ab', 'name' => 'First']]),
    ]);
    $this->artisan('address:seed')->assertSuccessful();
    putAppData(['countries/XXA/states.json' => json_encode([['code' => 'AB', 'name' => 'First']])]);

    $this->artisan('address:seed')->assertSuccessful();

    expect(State::query()->pluck('code')->all())->toContain('AB');
});

it('lists a postcode under two post offices', function () {
    putAppData([
        'countries.json' => json_encode([['code' => 'XXA', 'name' => 'Testland', 'alpha_2' => 'XA']]),
        'countries/XXA/states.json' => json_encode([['code' => '01', 'name' => 'First']]),
        'countries/XXA/states/01/post-offices.json' => json_encode([['name' => 'Town A', 'postcodes' => ['99999']], ['name' => 'Town B', 'postcodes' => ['99999']]]),
    ]);

    $this->artisan('address:seed')->assertSuccessful();

    expect(Postcode::query()->where('code', '99999')->count())->toBe(2);
});

it('writes nothing on a re-seed when a mutator changes the key', function () {
    putAppData([
        'countries.json' => json_encode([['code' => 'XXA', 'name' => 'Testland', 'alpha_2' => 'XA']]),
        'countries/XXA/states.json' => json_encode([['code' => '01', 'name' => 'First']]),
        'countries/XXA/states/01/post-offices.json' => json_encode([['name' => 'Town A', 'postcodes' => ['sw1a 1aa']]]),
    ]);
    $this->artisan('address:seed')->assertSuccessful();
    DB::enableQueryLog();

    $this->artisan('address:seed')->assertSuccessful();

    expect(Postcode::sole()->code)->toBe('SW1A 1AA')
        ->and(collect(DB::getQueryLog())->pluck('query'))->each->toStartWith('select');
});
