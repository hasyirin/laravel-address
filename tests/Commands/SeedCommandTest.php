<?php

use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\District;
use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;
use Hasyirin\Address\Models\Subdistrict;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

function putAppData(array $files): void
{
    foreach ($files as $file => $contents) {
        File::ensureDirectoryExists(dirname(base_path("data/$file")));
        File::put(base_path("data/$file"), $contents);
    }
}

beforeEach(function () {
    File::deleteDirectory(base_path('data'));
});

afterEach(function () {
    File::deleteDirectory(base_path('data'));
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

    expect(State::count())->toBe($rows('*/states.json'))
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
        ->and($postOffice->postcodes)->toBe(['72100', '72107', '72109'])
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

it('updates the live post office when a soft-deleted duplicate exists', function () {
    $this->artisan('address:seed')->assertSuccessful();

    $trashed = PostOffice::where('name', 'Bahau')->sole();
    $trashed->delete();
    $live = PostOffice::create(['state_id' => $trashed->state_id, 'name' => 'Bahau', 'postcodes' => ['72100']]);

    $this->artisan('address:seed')->assertSuccessful();

    expect($live->fresh()->postcodes)->toBe(['72100', '72107', '72109']);
});

it('seeds on a connection with a table prefix', function () {
    config([
        'database.connections.prefixed' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'app_'],
        'database.default' => 'prefixed',
    ]);

    foreach (['create_countries_table', 'create_states_table', 'create_post_offices_table', 'create_districts_table', 'create_subdistricts_table'] as $migration) {
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
    File::ensureDirectoryExists(base_path('data'));

    $this->artisan('address:seed')->assertSuccessful();

    expect(Country::where('code', 'MYS')->exists())->toBeTrue();
});

it('reports the file that holds invalid json', function () {
    putAppData(['countries.json' => '[{"code": "XXA",']);

    expect(fn () => $this->artisan('address:seed')->run())
        ->toThrow(RuntimeException::class, base_path('data/countries.json'));
});

it('resolves the local country from locality config after seeding', function () {
    config()->set('address.locality.country', 'MYS');

    $this->artisan('address:seed');

    $local = Country::local();

    expect($local)->not->toBeNull()
        ->and($local->code)->toBe('MYS');
});
