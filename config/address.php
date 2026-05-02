<?php

use Hasyirin\Address\Models\Address;
use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\District;
use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;
use Hasyirin\Address\Models\Subdistrict;

return [
    'locality' => [
        'country' => null,
        'state' => null,
        'district' => null,
    ],

    'models' => [
        'address' => Address::class,
        'country' => Country::class,
        'state' => State::class,
        'district' => District::class,
        'subdistrict' => Subdistrict::class,
        'post-office' => PostOffice::class,
    ],

    'tables' => [
        'addresses' => 'addresses',
        'countries' => 'countries',
        'states' => 'states',
        'districts' => 'districts',
        'subdistricts' => 'subdistricts',
        'post_offices' => 'post_offices',
    ],
];
