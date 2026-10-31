<?php

use Illuminate\Support\Collection;

function postOfficesOf(string $state): Collection
{
    return collect(json_decode(file_get_contents(__DIR__."/../data/countries/MYS/states/$state/post-offices.json"), true));
}

it('lists the postcodes that upstream added', function () {
    expect(postOfficesOf('01')->firstWhere('name', 'Ibrahim International Business District')['postcodes'] ?? [])->toContain('80888')
        ->and(postOfficesOf('14')->flatMap(fn (array $office) => $office['postcodes']))->toContain('55188');
});

it('drops the postcodes that upstream removed', function () {
    $postcodes = collect(glob(__DIR__.'/../data/countries/MYS/states/*/post-offices.json'))
        ->flatMap(fn (string $file) => collect(json_decode(file_get_contents($file), true))->flatMap(fn (array $office) => $office['postcodes']));

    expect($postcodes)->not->toContain('12344')
        ->not->toContain('71590')
        ->not->toContain('42160');
});

it('keeps the official spelling of Al Muktafi Billah Shah', function () {
    expect(postOfficesOf('11')->pluck('name'))->toContain('Al Muktafi Billah Shah')
        ->not->toContain('Al Muktatfi Billah Shah');
});

it('lists a shared postcode under both towns', function () {
    expect(postOfficesOf('01')->filter(fn (array $office) => in_array('84300', $office['postcodes'], true))->pluck('name')->sort()->values()->all())
        ->toBe(['Bukit Pasir', 'Muar']);
});
