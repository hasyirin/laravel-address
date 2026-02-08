<?php

use Hasyirin\Address\Models\Address;
use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\District;
use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;
use Hasyirin\Address\Tests\Fixtures\User;

beforeEach(function () {
    $this->country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
});

it('can create a state', function () {
    $state = State::create([
        'country_id' => $this->country->id,
        'code' => '10',
        'name' => 'Selangor',
        'local' => true,
    ]);

    expect($state)->toBeInstanceOf(State::class)
        ->and($state->code)->toBe('10')
        ->and($state->name)->toBe('Selangor')
        ->and($state->local)->toBeTrue();
});

it('defaults local to false', function () {
    $state = State::create([
        'country_id' => $this->country->id,
        'code' => '10',
        'name' => 'Selangor',
    ]);

    expect($state->local)->toBeFalse();
});

it('returns the local state via static method', function () {
    State::create(['country_id' => $this->country->id, 'code' => '10', 'name' => 'Selangor', 'local' => false]);
    $local = State::create(['country_id' => $this->country->id, 'code' => '14', 'name' => 'Kuala Lumpur', 'local' => true]);

    expect(State::local())->toBeInstanceOf(State::class)
        ->id->toBe($local->id);
});

// NOTE: State::country() has a bug — it uses config('address.models.state') instead of
// config('address.models.country'). This test documents the CORRECT expected behavior.
// It will fail until the bug is fixed.
it('belongs to a country', function () {
    $state = State::create(['country_id' => $this->country->id, 'code' => '10', 'name' => 'Selangor']);

    expect($state->country)->toBeInstanceOf(Country::class)
        ->id->toBe($this->country->id);
});

it('has many districts', function () {
    $state = State::create(['country_id' => $this->country->id, 'code' => '10', 'name' => 'Selangor']);
    District::create(['state_id' => $state->id, 'code' => '01', 'name' => 'Petaling']);
    District::create(['state_id' => $state->id, 'code' => '02', 'name' => 'Klang']);

    expect($state->districts)->toHaveCount(2);
});

it('has many post offices', function () {
    $state = State::create(['country_id' => $this->country->id, 'code' => '10', 'name' => 'Selangor']);
    PostOffice::create(['state_id' => $state->id, 'name' => 'Shah Alam', 'postcodes' => ['40000']]);

    expect($state->postOffices)->toHaveCount(1);
});

it('has many addresses', function () {
    $state = State::create(['country_id' => $this->country->id, 'code' => '10', 'name' => 'Selangor']);
    $user = User::create(['name' => 'Test']);

    Address::create([
        'addressable_type' => User::class,
        'addressable_id' => $user->id,
        'state_id' => $state->id,
        'type' => 'primary',
    ]);

    expect($state->addresses)->toHaveCount(1);
});

it('supports soft deletes', function () {
    $state = State::create(['country_id' => $this->country->id, 'code' => '10', 'name' => 'Selangor']);
    $state->delete();

    expect(State::count())->toBe(0)
        ->and(State::withTrashed()->count())->toBe(1);
});
