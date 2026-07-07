<?php

use Hasyirin\Address\Models\Address;
use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\District;
use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;
use Hasyirin\Address\Tests\Fixtures\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
});

it('can create a state', function () {
    $state = State::create([
        'country_id' => $this->country->id,
        'code' => '10',
        'name' => 'Selangor',
    ]);

    expect($state)->toBeInstanceOf(State::class)
        ->and($state->code)->toBe('10')
        ->and($state->name)->toBe('Selangor');
});

it('returns the local state via static method', function () {
    config([
        'address.locality.country' => 'MYS',
        'address.locality.state' => '14',
    ]);

    State::create(['country_id' => $this->country->id, 'code' => '10', 'name' => 'Selangor']);
    $local = State::create(['country_id' => $this->country->id, 'code' => '14', 'name' => 'Kuala Lumpur']);

    expect(State::local())->toBeInstanceOf(State::class)
        ->id->toBe($local->id);
});

it('returns null when locality state config is unset', function () {
    config([
        'address.locality.country' => 'MYS',
        'address.locality.state' => null,
    ]);

    State::create(['country_id' => $this->country->id, 'code' => '14', 'name' => 'Kuala Lumpur']);

    expect(State::local())->toBeNull();
});

it('returns null when locality country config is unset', function () {
    config([
        'address.locality.country' => null,
        'address.locality.state' => '14',
    ]);

    State::create(['country_id' => $this->country->id, 'code' => '14', 'name' => 'Kuala Lumpur']);

    expect(State::local())->toBeNull();
});

it('invalidates local() when the parent Country saves', function () {
    config([
        'address.locality.country' => 'MYS',
        'address.locality.state' => '14',
    ]);

    $kl = State::create(['country_id' => $this->country->id, 'code' => '14', 'name' => 'Kuala Lumpur']);

    expect(State::local()?->id)->toBe($kl->id);

    // Bypass model events so the State cache stays warm despite the country mutation.
    DB::table('countries')->where('id', $this->country->id)->update(['code' => 'XXX']);
    expect(State::local()?->id)->toBe($kl->id);

    // Saving the parent Country fires saved → State's listener clears the cache.
    $this->country->save();

    expect(State::local())->toBeNull();
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

it('generates a per-country unique code for every state the factory creates', function () {
    // states.code is unique per country. A batch sharing one country collided
    // intermittently while codes were drawn from a fixed 100-value pool.
    $count = 60;

    State::factory()->count($count)->create(['country_id' => $this->country->id]);

    expect(State::count())->toBe($count)
        ->and(State::pluck('code')->unique())->toHaveCount($count);
});
