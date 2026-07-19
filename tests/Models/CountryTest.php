<?php

use Hasyirin\Address\Models\Address;
use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\District;
use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;
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

it('caches local() across requests as a serialization-safe snapshot', function () {
    // Regression: local() cached the Eloquent model itself, which a persistent
    // store (e.g. redis) serializes and returns as __PHP_Incomplete_Class on a
    // later request. It must persist the raw attributes instead, then rehydrate.
    config([
        'cache.stores.persistent' => ['driver' => 'array', 'serialize' => true],
        'cache.default' => 'persistent',
        'address.locality.country' => 'MYS',
    ]);
    Country::create(['code' => 'MYS', 'name' => 'Malaysia']);

    expect(Country::local())->toBeInstanceOf(Country::class)
        ->and(Country::local()->code)->toBe('MYS');

    // Persisted for later requests, but as a plain attributes array — never a
    // serialized model (which would come back as __PHP_Incomplete_Class).
    $cached = cache()->store('persistent')->get('address.locality.'.Country::class);
    expect($cached)->toBeArray()
        ->and($cached['code'])->toBe('MYS');
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

it('supports soft deletes', function () {
    $country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    $country->delete();

    expect(Country::count())->toBe(0)
        ->and(Country::withTrashed()->count())->toBe(1);
});

it('generates a unique code for every country the factory creates', function () {
    // countries.code carries a global unique constraint. The factory must never
    // draw a duplicate, otherwise batches collide intermittently (birthday paradox
    // over Faker's fixed ~249-value ISO alpha-3 pool).
    $count = 100;

    Country::factory()->count($count)->create();

    expect(Country::count())->toBe($count)
        ->and(Country::pluck('code')->unique())->toHaveCount($count);
});
