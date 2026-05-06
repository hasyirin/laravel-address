<?php

use Hasyirin\Address\Models\Address;
use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\District;
use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;
use Hasyirin\Address\Tests\Fixtures\User;

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
