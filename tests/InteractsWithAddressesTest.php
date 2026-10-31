<?php

use Hasyirin\Address\Models\Address;
use Hasyirin\Address\Tests\Fixtures\AddressType;
use Hasyirin\Address\Tests\Fixtures\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

beforeEach(function () {
    $this->user = User::create(['name' => 'Test User']);
});

it('returns a morph-one for address() typed as primary', function () {
    $relation = $this->user->address();

    expect($relation)->toBeInstanceOf(MorphOne::class);
});

it('returns a morph-many for addresses()', function () {
    $relation = $this->user->addresses();

    expect($relation)->toBeInstanceOf(MorphMany::class);
});

it('returns primary address via address()', function () {
    Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'type' => 'primary',
            'line_1' => 'Primary Address',
        ]);

    Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'type' => 'billing',
            'line_1' => 'Billing Address',
        ]);

    expect($this->user->address->line_1)->toBe('Primary Address');
});

it('returns all addresses via addresses()', function () {
    Address::factory()
        ->for($this->user, 'addressable')
        ->create(['type' => 'primary']);

    Address::factory()
        ->for($this->user, 'addressable')
        ->create(['type' => 'billing']);

    expect($this->user->addresses)->toHaveCount(2);
});

it('returns the address of the given type', function () {
    Address::factory()->for($this->user, 'addressable')->create(['type' => 'billing', 'line_1' => 'Billing Address']);

    expect($this->user->address('billing')->first()?->line_1)->toBe('Billing Address');
});

it('returns a default address of the given type', function () {
    expect($this->user->address('shipping')->getResults())->toBeInstanceOf(Address::class)
        ->exists->toBeFalse()
        ->type->toBe('shipping');
});

it('sets the type from an enum when creating', function () {
    $address = $this->user->address(AddressType::Billing)->create(['line_1' => 'No. 1']);

    expect($address->fresh()->type)->toBe('billing');
});

it('sets the type when force creating', function () {
    $address = $this->user->address('billing')->forceCreate(['line_1' => 'No. 1']);

    expect($address->fresh()->type)->toBe('billing');
});

it('sets the type when saving a model through the relation', function () {
    $address = $this->user->address('billing')->save(new Address(['line_1' => 'No. 1']));

    expect($address->fresh()->type)->toBe('billing');
});
