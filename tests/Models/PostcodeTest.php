<?php

use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\Postcode;
use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

beforeEach(function () {
    $country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    $state = State::create(['country_id' => $country->id, 'code' => '10', 'name' => 'Selangor']);
    $this->postOffice = PostOffice::create(['state_id' => $state->id, 'name' => 'Shah Alam']);
});

it('belongs to a post office', function () {
    $postcode = $this->postOffice->postcodes()->create(['code' => '40000']);

    expect($postcode->postOffice->is($this->postOffice))->toBeTrue()
        ->and($this->postOffice->postcodes->pluck('code')->all())->toBe(['40000']);
});

it('rejects the same code twice in one post office', function () {
    $this->postOffice->postcodes()->create(['code' => '40000']);

    expect(fn () => $this->postOffice->postcodes()->create(['code' => '40000']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('allows one code in two post offices', function () {
    $other = PostOffice::create(['state_id' => $this->postOffice->state_id, 'name' => 'Klang']);
    $this->postOffice->postcodes()->create(['code' => '40000']);
    $other->postcodes()->create(['code' => '40000']);

    expect(Postcode::query()->where('code', '40000')->count())->toBe(2);
});

it('normalizes the code', function () {
    expect($this->postOffice->postcodes()->create(['code' => '  sw1a   1aa '])->code)->toBe('SW1A 1AA');
});

it('has no coordinates until an app sets them', function () {
    expect($this->postOffice->postcodes()->create(['code' => '40000'])->fresh())
        ->latitude->toBeNull()
        ->longitude->toBeNull();
});

it('makes a postcode with the factory', function () {
    expect(Postcode::factory()->create()->postOffice)->toBeInstanceOf(PostOffice::class);
});

it('works with the exists validation rule', function () {
    $this->postOffice->postcodes()->create(['code' => '40000']);
    $rule = Rule::exists(config('address.models.postcode'), 'code')
        ->where('post_office_id', $this->postOffice->id)
        ->withoutTrashed();

    expect(Validator::make(['postcode' => '40000'], ['postcode' => $rule])->passes())->toBeTrue()
        ->and(Validator::make(['postcode' => '40100'], ['postcode' => $rule])->passes())->toBeFalse();
});
