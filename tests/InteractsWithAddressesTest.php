<?php

use Hasyirin\Address\Models\Address;
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

it('returns address by type via getAddress()', function () {
    Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'type' => 'billing',
            'line_1' => 'Billing Address',
        ]);

    $billing = $this->user->getAddress('billing')->first();

    expect($billing)->not->toBeNull()
        ->and($billing->line_1)->toBe('Billing Address');
});

it('returns a default address with type when none exists', function () {
    $address = $this->user->getAddress('shipping')->first();

    // withDefault creates an unsaved model when no record exists
    expect($address)->toBeNull();

    // But accessing it via the dynamic property triggers withDefault
    $address = $this->user->getAddress('shipping')->getResults();

    expect($address)->toBeInstanceOf(Address::class)
        ->and($address->exists)->toBeFalse()
        ->and($address->type)->toBe('shipping');
});
