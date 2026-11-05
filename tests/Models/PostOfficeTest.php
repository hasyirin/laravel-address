<?php

use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;
use Hasyirin\Address\Tests\Fixtures\Branch;
use Hasyirin\Address\Tests\Fixtures\Province;
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

it('keeps only the post offices of the local state', function () {
    config(['address.locality.country' => 'MYS', 'address.locality.state' => '14']);

    $kualaLumpur = State::create(['country_id' => $this->state->country_id, 'code' => '14', 'name' => 'Kuala Lumpur']);
    $local = PostOffice::create(['state_id' => $kualaLumpur->id, 'name' => 'Kuala Lumpur']);
    PostOffice::create(['state_id' => $this->state->id, 'name' => 'Shah Alam']);

    expect(PostOffice::query()->local()->pluck('id')->all())->toBe([$local->id]);
});

it('does not keep the post offices of a state with the same code in another country', function () {
    config(['address.locality.country' => 'MYS', 'address.locality.state' => '10']);

    $singapore = Country::create(['code' => 'SGP', 'name' => 'Singapore']);
    $foreign = State::create(['country_id' => $singapore->id, 'code' => '10', 'name' => 'Central']);
    PostOffice::create(['state_id' => $foreign->id, 'name' => 'Orchard']);
    $local = PostOffice::create(['state_id' => $this->state->id, 'name' => 'Shah Alam']);

    expect(PostOffice::query()->local()->pluck('id')->all())->toBe([$local->id]);
});

it('finds no local post offices when the locality state code is null', function () {
    config(['address.locality.country' => 'MYS', 'address.locality.state' => null]);

    PostOffice::create(['state_id' => $this->state->id, 'name' => 'Shah Alam']);

    expect(PostOffice::query()->local()->count())->toBe(0);
});

it('finds no local post offices when the locality country code is null', function () {
    config(['address.locality.country' => null, 'address.locality.state' => '10']);

    PostOffice::create(['state_id' => $this->state->id, 'name' => 'Shah Alam']);

    expect(PostOffice::query()->local()->count())->toBe(0);
});

it('applies the local scope on subclasses from address.models', function () {
    config([
        'address.models.state' => Province::class,
        'address.models.post-office' => Branch::class,
        'address.locality.country' => 'MYS',
        'address.locality.state' => '10',
    ]);

    $kualaLumpur = Province::create(['country_id' => $this->state->country_id, 'code' => '14', 'name' => 'Kuala Lumpur']);
    Branch::create(['state_id' => $kualaLumpur->id, 'name' => 'Kuala Lumpur']);
    $local = Branch::create(['state_id' => $this->state->id, 'name' => 'Shah Alam']);

    expect(Branch::query()->local()->sole())->toBeInstanceOf(Branch::class)
        ->id->toBe($local->id);
});
