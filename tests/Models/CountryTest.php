<?php

use Hasyirin\Address\Models\Address;
use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\District;
use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;
use Hasyirin\Address\Tests\Fixtures\Nation;
use Hasyirin\Address\Tests\Fixtures\User;
use Illuminate\Support\Facades\DB;

it('can create a country', function () {
    $country = Country::create([
        'code' => 'MYS',
        'name' => 'Malaysia',
        'alpha_2' => 'MY',
    ]);

    expect($country)->toBeInstanceOf(Country::class)
        ->and($country->code)->toBe('MYS')
        ->and($country->name)->toBe('Malaysia')
        ->and($country->alpha_2)->toBe('MY');
});

it('returns the local country via static method', function () {
    config(['address.locality.country' => 'MYS']);

    Country::create(['code' => 'USA', 'name' => 'United States']);
    $local = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);

    expect(Country::local())->toBeInstanceOf(Country::class)
        ->id->toBe($local->id);
});

it('applies the local scope to a query that joins states', function () {
    config(['address.locality.country' => 'MYS']);

    $mys = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    State::create(['country_id' => $mys->id, 'code' => '10', 'name' => 'Selangor']);

    $country = Country::query()
        ->select('countries.*')
        ->join('states', 'states.country_id', '=', 'countries.id')
        ->local()
        ->first();

    expect($country?->id)->toBe($mys->id);
});

it('returns null when locality config is unset', function () {
    config(['address.locality.country' => null]);

    Country::create(['code' => 'MYS', 'name' => 'Malaysia']);

    expect(Country::local())->toBeNull();
});

it('returns null when no country matches the locality code', function () {
    config(['address.locality.country' => 'MYS']);

    Country::create(['code' => 'USA', 'name' => 'United States']);

    expect(Country::local())->toBeNull();
});

it('memoizes local() within a request and invalidates on save', function () {
    config(['address.locality.country' => 'MYS']);

    $mys = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);

    expect(Country::local()?->code)->toBe('MYS');

    // Bypass model events so the saved listener does not fire — cache stays warm.
    DB::table('countries')->where('id', $mys->id)->update(['code' => 'XXX']);

    expect(Country::local()?->code)->toBe('MYS');

    Country::clearLocalCache();

    expect(Country::local())->toBeNull();
});

it('follows a locality config change on a later request', function () {
    config([
        'cache.stores.persistent' => ['driver' => 'array', 'serialize' => true],
        'cache.default' => 'persistent',
        'address.locality.country' => 'MYS',
    ]);
    Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    Country::create(['code' => 'SGP', 'name' => 'Singapore']);

    expect(Country::local()?->code)->toBe('MYS');

    config(['address.locality.country' => 'SGP']);
    app()->forgetScopedInstances();

    expect(Country::local()?->code)->toBe('SGP');
});

it('clears the cached local() when the row changes under another locality config', function () {
    config([
        'cache.stores.persistent' => ['driver' => 'array', 'serialize' => true],
        'cache.default' => 'persistent',
        'address.locality.country' => 'MYS',
    ]);
    $mys = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);

    expect(Country::local()?->name)->toBe('Malaysia');

    config(['address.locality.country' => 'SGP']);
    $mys->update(['name' => 'Malaysia Baru']);

    config(['address.locality.country' => 'MYS']);
    app()->forgetScopedInstances();

    expect(Country::local()?->name)->toBe('Malaysia Baru');
});

it('finds a local country inserted without model events', function () {
    config(['address.locality.country' => 'MYS']);

    expect(Country::local())->toBeNull();

    Country::withoutEvents(fn () => Country::create(['code' => 'MYS', 'name' => 'Malaysia']));

    expect(Country::local()?->code)->toBe('MYS');
});

it('finds the local country when it is created after a miss', function () {
    config(['address.locality.country' => 'MYS']);

    expect(Country::local())->toBeNull();

    $mys = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);

    expect(Country::local()?->id)->toBe($mys->id);
});

it('returns a local() instance that Model::is() recognizes as the same record', function () {
    // Regression: local() rehydrates via newFromBuilder() without an explicit
    // connection, so it falls back to the query builder's own (never-connected)
    // model instance, leaving getConnectionName() null. Model::is() compares key,
    // table, AND connection name, so this silently broke local() as an identity
    // check (e.g. "is this the record I'm about to delete?").
    config(['address.locality.country' => 'MYS']);

    $country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);

    expect($country->is(Country::local()))->toBeTrue();
});

it('has many states', function () {
    $country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    State::create(['country_id' => $country->id, 'code' => '14', 'name' => 'Kuala Lumpur']);
    State::create(['country_id' => $country->id, 'code' => '10', 'name' => 'Selangor']);

    expect($country->states)->toHaveCount(2);
});

it('has many districts through states', function () {
    $country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    $state = State::create(['country_id' => $country->id, 'code' => '10', 'name' => 'Selangor']);
    District::create(['state_id' => $state->id, 'code' => '01', 'name' => 'Petaling']);

    expect($country->districts)->toHaveCount(1)
        ->first()->name->toBe('Petaling');
});

it('has many post offices through states', function () {
    $country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    $state = State::create(['country_id' => $country->id, 'code' => '10', 'name' => 'Selangor']);
    PostOffice::create(['state_id' => $state->id, 'name' => 'Shah Alam', 'postcodes' => ['40000']]);

    expect($country->postOffices)->toHaveCount(1)
        ->first()->name->toBe('Shah Alam');
});

it('has many addresses', function () {
    $country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    $user = User::create(['name' => 'Test']);

    Address::create([
        'addressable_type' => User::class,
        'addressable_id' => $user->id,
        'country_id' => $country->id,
        'type' => 'primary',
    ]);

    expect($country->addresses)->toHaveCount(1);
});

it('resolves its relations when the country model has another class name', function () {
    config(['address.models.country' => Nation::class]);

    $nation = Nation::create(['code' => 'MYS', 'name' => 'Malaysia']);
    $state = State::create(['country_id' => $nation->id, 'code' => '10', 'name' => 'Selangor']);
    District::create(['state_id' => $state->id, 'code' => '01', 'name' => 'Petaling']);
    PostOffice::create(['state_id' => $state->id, 'name' => 'Shah Alam', 'postcodes' => ['40000']]);
    Address::create([
        'addressable_type' => User::class,
        'addressable_id' => User::create(['name' => 'Test'])->id,
        'country_id' => $nation->id,
        'type' => 'primary',
    ]);

    expect($nation->states)->toHaveCount(1)
        ->and($nation->districts)->toHaveCount(1)
        ->and($nation->postOffices)->toHaveCount(1)
        ->and($nation->addresses)->toHaveCount(1);
});

it('supports soft deletes', function () {
    $country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    $country->delete();

    expect(Country::count())->toBe(0)
        ->and(Country::withTrashed()->count())->toBe(1);
});

it('generates a unique code for every country the factory creates', function () {
    // countries.code carries a global unique constraint. The factory must never
    // draw a duplicate, otherwise batches collide intermittently (birthday paradox
    // over the factory's fixed code pool).
    $count = 100;

    Country::factory()->count($count)->create();

    expect(Country::count())->toBe($count)
        ->and(Country::pluck('code')->unique())->toHaveCount($count);
});

it('creates a country with the factory after seeding', function () {
    $this->artisan('address:seed')->assertSuccessful();

    expect(Country::factory()->create()->exists)->toBeTrue();
});
