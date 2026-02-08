<?php

use Hasyirin\Address\Models\Address;
use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;
use Hasyirin\Address\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Test User']);
    $this->country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    $this->state = State::create(['country_id' => $this->country->id, 'code' => '10', 'name' => 'Selangor']);
    $this->postOffice = PostOffice::create([
        'state_id' => $this->state->id,
        'name' => 'Shah Alam',
        'postcodes' => ['40000'],
    ]);
});

it('can create an address', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'country_id' => $this->country->id,
            'state_id' => $this->state->id,
            'post_office_id' => $this->postOffice->id,
            'type' => 'primary',
            'line_1' => 'No. 1, Jalan Test',
            'line_2' => 'Taman Test',
            'postcode' => '40000',
        ])->fresh();

    expect($address)->toBeInstanceOf(Address::class)
        ->and($address->type)->toBe('primary')
        ->and($address->line_1)->toBe('No. 1, Jalan Test')
        ->and($address->postcode)->toBe('40000');
});

it('casts properties to array', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'properties' => ['floor' => '3', 'unit' => 'A'],
        ]);

    $fresh = $address->fresh();
    expect($fresh->properties)->toBeArray()->toBe(['floor' => '3', 'unit' => 'A']);
});

it('defaults properties to empty array', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create();

    expect($address->properties)->toBe([]);
});

it('belongs to a country', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create(['country_id' => $this->country->id]);

    expect($address->country)->toBeInstanceOf(Country::class)
        ->id->toBe($this->country->id);
});

it('belongs to a state', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create(['state_id' => $this->state->id]);

    expect($address->state)->toBeInstanceOf(State::class)
        ->id->toBe($this->state->id);
});

it('belongs to a post office', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create(['post_office_id' => $this->postOffice->id]);

    expect($address->postOffice)->toBeInstanceOf(PostOffice::class)
        ->id->toBe($this->postOffice->id);
});

it('has a morph-to addressable relationship', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create();

    expect($address->addressable)->toBeInstanceOf(User::class)
        ->id->toBe($this->user->id);
});

it('squishes whitespace on line fields when saving', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'line_1' => '  No. 1,   Jalan  Test  ',
            'line_2' => '  Taman   Test  ',
            'line_3' => '  Area   Test  ',
        ])->fresh();

    expect($address->line_1)->toBe('No. 1, Jalan Test')
        ->and($address->line_2)->toBe('Taman Test')
        ->and($address->line_3)->toBe('Area Test');
});

it('trims trailing commas on line fields when saving', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'line_1' => 'No. 1, Jalan Test,',
            'line_2' => 'Taman Test,',
            'line_3' => 'Area Test,',
        ])->fresh();

    expect($address->line_1)->toBe('No. 1, Jalan Test')
        ->and($address->line_2)->toBe('Taman Test')
        ->and($address->line_3)->toBe('Area Test');
});

// -- formatted() --

it('formats address with all parts', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'country_id' => $this->country->id,
            'state_id' => $this->state->id,
            'post_office_id' => $this->postOffice->id,
            'line_1' => 'No. 1, Jalan Test',
            'line_2' => 'Taman Test',
            'postcode' => '40000',
        ]);

    $formatted = $address->formatted();

    expect($formatted)->toContain('No. 1, Jalan Test')
        ->toContain('Taman Test')
        ->toContain('40000')
        ->toContain('Shah Alam')
        ->toContain('Selangor')
        ->toContain('Malaysia');
});

it('formats address without state', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'country_id' => $this->country->id,
            'state_id' => $this->state->id,
            'line_1' => 'No. 1',
            'postcode' => '40000',
        ]);

    $formatted = $address->formatted(state: false);

    expect($formatted)->not->toContain('Selangor')
        ->toContain('Malaysia');
});

it('formats address without country', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'country_id' => $this->country->id,
            'state_id' => $this->state->id,
            'line_1' => 'No. 1',
            'postcode' => '40000',
        ]);

    $formatted = $address->formatted(country: false);

    expect($formatted)->toContain('Selangor')
        ->not->toContain('Malaysia');
});

it('formats address capitalized', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'line_1' => 'No. 1, Jalan Test',
            'line_2' => null,
            'postcode' => null,
        ])->fresh();

    $formatted = $address->formatted(state: false, country: false, capitalize: true);

    expect($formatted)->toBe('NO. 1, JALAN TEST');
});

// -- render() --

it('renders address inline', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'state_id' => $this->state->id,
            'line_1' => 'No. 1, Jalan Test',
            'postcode' => '40000',
        ]);

    $rendered = $address->render(inline: true, country: false);

    expect($rendered)->not->toContain('<p')
        ->toContain('No. 1, Jalan Test');
});

it('renders address as block with p tags', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'state_id' => $this->state->id,
            'line_1' => 'No. 1, Jalan Test',
            'postcode' => '40000',
        ]);

    $rendered = $address->render(country: false);

    expect($rendered)->toContain('<p class="mb-0">')
        ->toContain('No. 1, Jalan Test');
});

it('renders address with custom margin', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create(['line_1' => 'No. 1']);

    $rendered = $address->render(state: false, country: false, margin: 2);

    expect($rendered)->toContain('mb-2');
});

it('renders address capitalized', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create(['line_1' => 'No. 1, Jalan Test']);

    $rendered = $address->render(inline: true, state: false, country: false, capitalize: true);

    expect($rendered)->toContain('NO. 1, JALAN TEST');
});

it('copy an address without addressable or type', function () {
    $original = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'country_id' => $this->country->id,
            'state_id' => $this->state->id,
            'post_office_id' => $this->postOffice->id,
            'type' => 'primary',
            'line_1' => 'No. 1',
            'line_2' => 'Taman Test',
            'postcode' => '40000',
            'latitude' => 3.1,
            'longitude' => 101.6,
            'properties' => ['floor' => '3'],
        ])->fresh();

    $copy = $original->copy();

    expect($copy->exists)->toBeFalse()
        ->and($copy->addressable_type)->toBeNull()
        ->and($copy->addressable_id)->toBeNull()
        ->and($copy->type)->toBeNull()
        ->and($copy->country_id)->toBe($this->country->id)
        ->and($copy->state_id)->toBe($this->state->id)
        ->and($copy->post_office_id)->toBe($this->postOffice->id)
        ->and($copy->line_1)->toBe('No. 1')
        ->and($copy->line_2)->toBe('Taman Test')
        ->and($copy->postcode)->toBe('40000');
});

// -- scopeOfType --

it('scopes addresses by type string', function () {
    Address::factory()
        ->for($this->user, 'addressable')
        ->create(['type' => 'primary']);
    Address::factory()
        ->for($this->user, 'addressable')
        ->create(['type' => 'billing']);

    expect(Address::ofType('primary')->count())->toBe(1)
        ->and(Address::ofType('billing')->count())->toBe(1);
});

it('scopes addresses by type array', function () {
    Address::factory()
        ->for($this->user, 'addressable')
        ->create(['type' => 'primary']);
    Address::factory()
        ->for($this->user, 'addressable')
        ->create(['type' => 'billing']);
    Address::factory()
        ->for($this->user, 'addressable')
        ->create(['type' => 'shipping']);

    expect(Address::ofType(['primary', 'billing'])->count())->toBe(2);
});

// -- soft deletes --

it('supports soft deletes', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create();
    $address->delete();

    expect(Address::count())->toBe(0)
        ->and(Address::withTrashed()->count())->toBe(1);
});
