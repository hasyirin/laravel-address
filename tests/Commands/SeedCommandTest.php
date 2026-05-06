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

it('resolves the local country from locality config after seeding', function () {
    config()->set('address.locality.country', 'MYS');

    $this->artisan('address:seed');

    $local = Country::local();

    expect($local)->not->toBeNull()
        ->and($local->code)->toBe('MYS');
});
