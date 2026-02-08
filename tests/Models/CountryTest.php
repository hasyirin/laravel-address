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
        'local' => true,
    ]);

    expect($country)->toBeInstanceOf(Country::class)
        ->and($country->code)->toBe('MYS')
        ->and($country->name)->toBe('Malaysia')
        ->and($country->alpha_2)->toBe('MY')
        ->and($country->local)->toBeTrue();
});

it('casts local to boolean', function () {
    $country = Country::create([
        'code' => 'MYS',
        'name' => 'Malaysia',
        'local' => 1,
    ]);

    expect($country->local)->toBeBool()->toBeTrue();

    $country2 = Country::create([
        'code' => 'USA',
        'name' => 'United States',
        'local' => 0,
    ]);

    expect($country2->local)->toBeBool()->toBeFalse();
});

it('defaults local to false', function () {
    $country = Country::create([
        'code' => 'GBR',
        'name' => 'United Kingdom',
    ]);

    expect($country->local)->toBeFalse();
});

it('returns the local country via static method', function () {
    Country::create(['code' => 'USA', 'name' => 'United States', 'local' => false]);
    $local = Country::create(['code' => 'MYS', 'name' => 'Malaysia', 'local' => true]);

    expect(Country::local())->toBeInstanceOf(Country::class)
        ->id->toBe($local->id);
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
