<?php

use Hasyirin\Address\Models\Address;
use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\District;
use Hasyirin\Address\Models\Postcode;
use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;
use Hasyirin\Address\Models\Subdistrict;
use Hasyirin\Address\Tests\Fixtures\User;

use function PHPStan\Testing\assertType;

function relationTypes(User $user, Address $address, Country $country, State $state, District $district, Subdistrict $subdistrict, PostOffice $postOffice, Postcode $postcode): void
{
    assertType('Illuminate\Database\Eloquent\Relations\MorphMany<Hasyirin\Address\Models\Address, Hasyirin\Address\Tests\Fixtures\User>', $user->addresses());
    assertType('Illuminate\Database\Eloquent\Relations\MorphOne<Hasyirin\Address\Models\Address, Hasyirin\Address\Tests\Fixtures\User>', $user->address());
    assertType('Hasyirin\Address\Models\Address', $user->address()->updateOrCreate([], []));

    assertType('Illuminate\Database\Eloquent\Relations\MorphTo<Illuminate\Database\Eloquent\Model, Hasyirin\Address\Models\Address>', $address->addressable());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<Hasyirin\Address\Models\Country, Hasyirin\Address\Models\Address>', $address->country());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<Hasyirin\Address\Models\State, Hasyirin\Address\Models\Address>', $address->state());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<Hasyirin\Address\Models\District, Hasyirin\Address\Models\Address>', $address->district());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<Hasyirin\Address\Models\Subdistrict, Hasyirin\Address\Models\Address>', $address->subdistrict());
    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<Hasyirin\Address\Models\PostOffice, Hasyirin\Address\Models\Address>', $address->postOffice());

    assertType('Illuminate\Database\Eloquent\Relations\HasMany<Hasyirin\Address\Models\State, Hasyirin\Address\Models\Country>', $country->states());
    assertType('Illuminate\Database\Eloquent\Relations\HasManyThrough<Hasyirin\Address\Models\District, Hasyirin\Address\Models\State, Hasyirin\Address\Models\Country>', $country->districts());
    assertType('Illuminate\Database\Eloquent\Relations\HasManyThrough<Hasyirin\Address\Models\PostOffice, Hasyirin\Address\Models\State, Hasyirin\Address\Models\Country>', $country->postOffices());
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<Hasyirin\Address\Models\Address, Hasyirin\Address\Models\Country>', $country->addresses());

    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<Hasyirin\Address\Models\Country, Hasyirin\Address\Models\State>', $state->country());
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<Hasyirin\Address\Models\District, Hasyirin\Address\Models\State>', $state->districts());
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<Hasyirin\Address\Models\PostOffice, Hasyirin\Address\Models\State>', $state->postOffices());
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<Hasyirin\Address\Models\Address, Hasyirin\Address\Models\State>', $state->addresses());

    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<Hasyirin\Address\Models\State, Hasyirin\Address\Models\District>', $district->state());
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<Hasyirin\Address\Models\Subdistrict, Hasyirin\Address\Models\District>', $district->subdistricts());

    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<Hasyirin\Address\Models\District, Hasyirin\Address\Models\Subdistrict>', $subdistrict->district());

    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<Hasyirin\Address\Models\State, Hasyirin\Address\Models\PostOffice>', $postOffice->state());
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<Hasyirin\Address\Models\Postcode, Hasyirin\Address\Models\PostOffice>', $postOffice->postcodes());

    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<Hasyirin\Address\Models\PostOffice, Hasyirin\Address\Models\Postcode>', $postcode->postOffice());

    assertType('Illuminate\Support\Collection<int, Hasyirin\Address\Models\State>', $country->states);
    assertType('Illuminate\Support\Collection<int, Hasyirin\Address\Models\Postcode>', $postOffice->postcodes);
    assertType('Illuminate\Support\Collection<int, Hasyirin\Address\Models\Subdistrict>', $district->subdistricts);
}
