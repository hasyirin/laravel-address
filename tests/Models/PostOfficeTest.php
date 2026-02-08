<?php

use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;

beforeEach(function () {
    $country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    $this->state = State::create(['country_id' => $country->id, 'code' => '10', 'name' => 'Selangor']);
});

it('can create a post office', function () {
    $postOffice = PostOffice::create([
        'state_id' => $this->state->id,
        'name' => 'Shah Alam',
        'postcodes' => ['40000', '40100'],
    ]);

    expect($postOffice)->toBeInstanceOf(PostOffice::class)
        ->and($postOffice->name)->toBe('Shah Alam')
        ->and($postOffice->postcodes)->toBe(['40000', '40100']);
});

it('casts postcodes to array', function () {
    $postOffice = PostOffice::create([
        'state_id' => $this->state->id,
        'name' => 'Shah Alam',
        'postcodes' => ['40000'],
    ]);

    $fresh = $postOffice->fresh();

    expect($fresh->postcodes)->toBeArray()->toBe(['40000']);
});

it('defaults postcodes to empty array', function () {
    $postOffice = PostOffice::create([
        'state_id' => $this->state->id,
        'name' => 'Shah Alam',
    ]);

    expect($postOffice->postcodes)->toBeArray()->toBe([]);
});

it('belongs to a state', function () {
    $postOffice = PostOffice::create([
        'state_id' => $this->state->id,
        'name' => 'Shah Alam',
    ]);

    expect($postOffice->state)->toBeInstanceOf(State::class)
        ->id->toBe($this->state->id);
});

it('supports soft deletes', function () {
    $postOffice = PostOffice::create([
        'state_id' => $this->state->id,
        'name' => 'Shah Alam',
    ]);
    $postOffice->delete();

    expect(PostOffice::count())->toBe(0)
        ->and(PostOffice::withTrashed()->count())->toBe(1);
});
