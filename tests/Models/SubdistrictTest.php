<?php

use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\District;
use Hasyirin\Address\Models\State;
use Hasyirin\Address\Models\Subdistrict;

beforeEach(function () {
    $country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    $state = State::create(['country_id' => $country->id, 'code' => '10', 'name' => 'Selangor']);
    $this->district = District::create(['state_id' => $state->id, 'code' => '01', 'name' => 'Petaling']);
});

it('can create a subdistrict', function () {
    $subdistrict = Subdistrict::create([
        'district_id' => $this->district->id,
        'code' => '01',
        'name' => 'Mukim Petaling',
    ]);

    expect($subdistrict)->toBeInstanceOf(Subdistrict::class)
        ->and($subdistrict->code)->toBe('01')
        ->and($subdistrict->name)->toBe('Mukim Petaling');
});

it('belongs to a district', function () {
    $subdistrict = Subdistrict::create([
        'district_id' => $this->district->id,
        'code' => '01',
        'name' => 'Mukim Petaling',
    ]);

    expect($subdistrict->district)->toBeInstanceOf(District::class)
        ->id->toBe($this->district->id);
});

it('supports soft deletes', function () {
    $subdistrict = Subdistrict::create([
        'district_id' => $this->district->id,
        'code' => '01',
        'name' => 'Mukim Petaling',
    ]);
    $subdistrict->delete();

    expect(Subdistrict::count())->toBe(0)
        ->and(Subdistrict::withTrashed()->count())->toBe(1);
});
