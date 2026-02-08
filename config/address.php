<?php

return [
    'locality' => [
        'country' => null,
        'state' => null,
        'district' => null,
    ],

    'models' => [
        'address' => \Hasyirin\Address\Models\Address::class,
        'country' => \Hasyirin\Address\Models\Country::class,
        'state' => \Hasyirin\Address\Models\State::class,
        'district' => \Hasyirin\Address\Models\District::class,
        'subdistrict' => \Hasyirin\Address\Models\Subdistrict::class,
        'post-office' => \Hasyirin\Address\Models\PostOffice::class,
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
