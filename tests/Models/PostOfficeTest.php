<?php

use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;
use Illuminate\Database\UniqueConstraintViolationException;

beforeEach(function () {
    $country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    $this->state = State::create(['country_id' => $country->id, 'code' => '10', 'name' => 'Selangor']);
});

it('can create a post office', function () {
    $postOffice = PostOffice::create([
        'state_id' => $this->state->id,
        'name' => 'Shah Alam',
    ]);

    expect($postOffice)->toBeInstanceOf(PostOffice::class)
        ->and($postOffice->name)->toBe('Shah Alam');
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

it('rejects a second post office with the same name in a state', function () {
    PostOffice::create(['state_id' => $this->state->id, 'name' => 'Shah Alam']);

    expect(fn () => PostOffice::create(['state_id' => $this->state->id, 'name' => 'Shah Alam']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('generates a unique name for every post office the factory creates in one state', function () {
    PostOffice::factory()->count(60)->create(['state_id' => $this->state->id]);

    expect(PostOffice::pluck('name')->unique())->toHaveCount(60);
});
