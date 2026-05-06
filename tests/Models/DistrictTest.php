<?php

use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\District;
use Hasyirin\Address\Models\State;
use Hasyirin\Address\Models\Subdistrict;

beforeEach(function () {
    $this->country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    $this->state = State::create(['country_id' => $this->country->id, 'code' => '10', 'name' => 'Selangor']);
});

it('can create a district', function () {
    $district = District::create([
        'state_id' => $this->state->id,
        'code' => '01',
        'name' => 'Petaling',
    ]);

    expect($district)->toBeInstanceOf(District::class)
        ->and($district->code)->toBe('01')
        ->and($district->name)->toBe('Petaling');
});

it('belongs to a state', function () {
    $district = District::create(['state_id' => $this->state->id, 'code' => '01', 'name' => 'Petaling']);

    expect($district->state)->toBeInstanceOf(State::class)
        ->id->toBe($this->state->id);
});

it('has many subdistricts', function () {
    $district = District::create(['state_id' => $this->state->id, 'code' => '01', 'name' => 'Petaling']);
    Subdistrict::create(['district_id' => $district->id, 'code' => '01', 'name' => 'Mukim A']);
    Subdistrict::create(['district_id' => $district->id, 'code' => '02', 'name' => 'Mukim B']);

    expect($district->subdistricts)->toHaveCount(2);
});

it('returns the local district via static method', function () {
    config([
        'address.locality.country' => 'MYS',
        'address.locality.state' => '10',
        'address.locality.district' => '02',
    ]);

    District::create(['state_id' => $this->state->id, 'code' => '01', 'name' => 'Petaling']);
    $local = District::create(['state_id' => $this->state->id, 'code' => '02', 'name' => 'Klang']);

    expect(District::local())->toBeInstanceOf(District::class)
        ->id->toBe($local->id);
});

it('returns null when locality district config is unset', function () {
    config([
        'address.locality.country' => 'MYS',
        'address.locality.state' => '10',
        'address.locality.district' => null,
    ]);

    District::create(['state_id' => $this->state->id, 'code' => '02', 'name' => 'Klang']);

    expect(District::local())->toBeNull();
});

it('returns null when an ancestor locality config is unset', function () {
    config([
        'address.locality.country' => 'MYS',
        'address.locality.state' => null,
        'address.locality.district' => '02',
    ]);

    District::create(['state_id' => $this->state->id, 'code' => '02', 'name' => 'Klang']);

    expect(District::local())->toBeNull();
});

it('supports soft deletes', function () {
    $district = District::create(['state_id' => $this->state->id, 'code' => '01', 'name' => 'Petaling']);
    $district->delete();

    expect(District::count())->toBe(0)
        ->and(District::withTrashed()->count())->toBe(1);
});
