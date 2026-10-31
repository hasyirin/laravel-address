<?php

use Hasyirin\Address\Models\Address;
use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\District;
use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;
use Hasyirin\Address\Models\Subdistrict;
use Hasyirin\Address\Tests\Fixtures\EncryptedAddress;
use Hasyirin\Address\Tests\Fixtures\PlainCountry;
use Hasyirin\Address\Tests\Fixtures\User;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::create(['name' => 'Test User']);
    $this->country = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    $this->state = State::create(['country_id' => $this->country->id, 'code' => '10', 'name' => 'Selangor']);
    $this->postOffice = PostOffice::create([
        'state_id' => $this->state->id,
        'name' => 'Shah Alam',
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
    expect($fresh->properties)->toBeArray()->toEqual(['floor' => '3', 'unit' => 'A']);
});

it('casts coordinates to float', function () {
    $address = new Address(['latitude' => '3.1390', 'longitude' => '101.6869']);

    expect($address->latitude)->toBe(3.139)
        ->and($address->longitude)->toBe(101.6869);
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

it('clears the state when the state is force deleted', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create(['state_id' => $this->state->id]);

    $this->state->forceDelete();

    expect($address->fresh()->state_id)->toBeNull();
});

it('belongs to a district and a subdistrict', function () {
    $district = District::create(['state_id' => $this->state->id, 'code' => '01', 'name' => 'Petaling']);
    $subdistrict = Subdistrict::create(['district_id' => $district->id, 'code' => '01', 'name' => 'Damansara']);

    $address = Address::factory()->for($this->user, 'addressable')
        ->create(['district_id' => $district->id, 'subdistrict_id' => $subdistrict->id]);

    expect($address->district?->is($district))->toBeTrue()
        ->and($address->subdistrict?->is($subdistrict))->toBeTrue();
});

it('keeps soft-deleted places on the address', function () {
    $district = District::create(['state_id' => $this->state->id, 'code' => '01', 'name' => 'Petaling']);
    $subdistrict = Subdistrict::create(['district_id' => $district->id, 'code' => '01', 'name' => 'Damansara']);
    $address = Address::factory()->for($this->user, 'addressable')->create([
        'country_id' => $this->country->id, 'state_id' => $this->state->id, 'district_id' => $district->id,
        'subdistrict_id' => $subdistrict->id, 'post_office_id' => $this->postOffice->id,
    ]);

    foreach ([$this->country, $this->state, $district, $subdistrict, $this->postOffice] as $place) {
        $place->delete();
    }
    $fresh = $address->fresh();

    expect([$fresh->country, $fresh->state, $fresh->district, $fresh->subdistrict, $fresh->postOffice])
        ->each->not->toBeNull();
});

it('does not mass assign the owner keys', function () {
    $address = new Address(['addressable_type' => User::class, 'addressable_id' => 1, 'type' => 'primary']);

    expect($address->addressable_type)->toBeNull()
        ->and($address->addressable_id)->toBeNull();
});

it('loads a place whose model does not use soft deletes', function () {
    config(['address.models.country' => PlainCountry::class]);
    $address = Address::factory()->for($this->user, 'addressable')->create(['country_id' => $this->country->id]);

    expect($address->country)->toBeInstanceOf(PlainCountry::class);
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

it('keeps empty line fields null when saving', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create(['line_2' => null, 'line_3' => null]);

    $row = DB::table('addresses')->where('id', $address->id)->first();

    expect($row->line_2)->toBeNull()
        ->and($row->line_3)->toBeNull();
});

it('keeps line fields that were not selected when saving', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'type' => 'primary',
            'line_1' => 'No. 1',
            'line_2' => 'Taman Test',
        ]);

    $partial = Address::query()->select(['id', 'type'])->find($address->id);
    $partial->type = 'billing';
    $partial->save();

    expect($address->fresh())
        ->type->toBe('billing')
        ->line_1->toBe('No. 1')
        ->line_2->toBe('Taman Test');
});

it('does not update an unchanged address when saving', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create()
        ->fresh();

    $address->save();

    expect($address->getChanges())->toBe([]);
});

it('normalizes line fields through subclass casts', function () {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);

    $address = new EncryptedAddress([
        'type' => 'primary',
        'line_1' => '  No. 1,   Jalan Test,  ',
    ]);
    $address->addressable()->associate($this->user)->save();

    expect($address->fresh()->line_1)->toBe('No. 1, Jalan Test')
        ->and(DB::table('addresses')->where('id', $address->id)->value('line_1'))->not->toBe('No. 1, Jalan Test');
});

it('reports an unselected line field under strict mode', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create();

    Model::preventAccessingMissingAttributes();

    try {
        $partial = Address::query()->select(['id'])->find($address->id);

        expect(fn () => $partial->line_1)->toThrow(MissingAttributeException::class);
    } finally {
        Model::preventAccessingMissingAttributes(false);
    }
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

it('capitalizes multibyte characters when formatting', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'line_1' => 'Jalan Café',
            'line_2' => null,
            'postcode' => null,
        ])->fresh();

    expect($address->formatted(state: false, country: false, capitalize: true))->toBe('JALAN CAFÉ');
});

it('skips empty line fields when formatting before a refresh', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'line_1' => 'No. 1',
            'line_2' => null,
            'line_3' => null,
            'postcode' => '40000',
        ]);

    expect($address->formatted())->toBe('No. 1, 40000');
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

it('capitalizes multibyte characters when rendering', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'line_1' => 'Jalan Café',
            'line_2' => null,
            'postcode' => null,
        ])->fresh();

    expect($address->render(inline: true, state: false, country: false, capitalize: true))->toBe('JALAN CAFÉ');
});

it('escapes html when rendering as block', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'line_1' => '<script>alert(1)</script>',
            'line_2' => null,
            'postcode' => '40000',
        ])->fresh();

    expect($address->render(state: false, country: false))
        ->toBe("<p class=\"mb-0\">&lt;script&gt;alert(1)&lt;/script&gt;,</p>\n<p class=\"mb-0\">40000</p>");
});

it('keeps inline output as plain text', function () {
    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'line_1' => "Jalan Dato' Onn",
            'line_2' => 'Lot 5 & 6',
            'postcode' => '40000',
        ])->fresh();

    expect($address->render(inline: true, state: false, country: false))
        ->toBe("Jalan Dato' Onn,\nLot 5 & 6,\n40000");
});

it('drops parts that are empty after trimming when rendering capitalized', function () {
    $postOffice = PostOffice::create([
        'state_id' => $this->state->id,
        'name' => 'Kukup',
    ]);

    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'post_office_id' => $postOffice->id,
            'line_1' => null,
            'line_2' => null,
            'postcode' => ',',
        ])->fresh();

    expect($address->render(state: false, country: false, capitalize: true))
        ->toBe('<p class="mb-0">KUKUP</p>');
});

it('keeps the last character of the last line when rendering as block', function () {
    $postOffice = PostOffice::create([
        'state_id' => $this->state->id,
        'name' => 'Kukup',
    ]);

    $address = Address::factory()
        ->for($this->user, 'addressable')
        ->create([
            'post_office_id' => $postOffice->id,
            'line_1' => 'No. 1',
            'line_2' => null,
            'postcode' => '82300',
        ])->fresh();

    expect($address->render(state: false, country: false))
        ->toBe("<p class=\"mb-0\">No. 1,</p>\n<p class=\"mb-0\">82300, Kukup</p>");
});

it('renders an empty string for an address without parts', function () {
    expect($this->user->address->render())->toBe('');
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

it('copies a trashed address as not trashed', function () {
    $original = Address::factory()
        ->for($this->user, 'addressable')
        ->create();

    $original->delete();

    expect($original->copy()->deleted_at)->toBeNull();
});

it('copies an address without its loaded addressable', function () {
    $original = Address::factory()
        ->for($this->user, 'addressable')
        ->create();

    $original->load('addressable');

    expect($original->copy()->relationLoaded('addressable'))->toBeFalse();
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
