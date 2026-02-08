<?php

use Hasyirin\Address\Models\Country;

it('runs the seed command successfully', function () {
    $this->artisan('address:seed')
        ->assertSuccessful();
});

it('seeds countries from data file', function () {
    $this->artisan('address:seed');

    $countriesData = json_decode(
        file_get_contents(__DIR__.'/../../data/countries.json'),
        true
    );

    expect(Country::count())->toBe(count($countriesData));
});

it('sets the local flag based on config', function () {
    config()->set('address.locality.country', 'MYS');

    $this->artisan('address:seed');

    $malaysia = Country::where('code', 'MYS')->first();

    expect($malaysia)->not->toBeNull()
        ->and($malaysia->local)->toBeTrue();

    // Other countries should not be local
    $nonLocal = Country::where('code', '!=', 'MYS')->where('local', true)->count();
    expect($nonLocal)->toBe(0);
});
