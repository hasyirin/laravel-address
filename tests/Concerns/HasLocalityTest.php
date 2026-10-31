<?php

use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\District;
use Hasyirin\Address\Models\State;
use Hasyirin\Address\Tests\Fixtures\Nation;
use Illuminate\Support\Facades\DB;

it('shares the memo with a configured subclass', function () {
    config(['address.models.country' => Nation::class, 'address.locality.country' => 'MYS']);
    $mys = Nation::create(['code' => 'MYS', 'name' => 'Malaysia']);

    expect(Nation::local()?->name)->toBe('Malaysia');

    Country::query()->find($mys->id)->update(['name' => 'Malaysia Baru']);

    expect(Nation::local())->toBeInstanceOf(Nation::class)
        ->name->toBe('Malaysia Baru');
});

it('keeps the result for one request only', function () {
    config(['address.locality.country' => 'MYS']);
    $mys = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    Country::local();
    DB::table('countries')->where('id', $mys->id)->update(['name' => 'Malaysia Baru']);

    expect(Country::local()?->name)->toBe('Malaysia');

    app()->forgetScopedInstances();

    expect(Country::local()?->name)->toBe('Malaysia Baru');
});

it('does not use the cache store', function () {
    config(['address.locality.country' => 'MYS', 'cache.default' => 'database']);
    $mys = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    $mys->update(['name' => 'Malaysia Baru']);

    expect(Country::local()?->name)->toBe('Malaysia Baru');
});

it('drops a change that rolls back', function () {
    config(['address.locality.country' => 'MYS']);
    $mys = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    Country::local();

    rescue(fn () => DB::transaction(function () use ($mys) {
        $mys->update(['name' => 'Malaysia Baru']);
        Country::local();

        throw new RuntimeException;
    }), report: false);

    expect(Country::local()?->name)->toBe('Malaysia');
});

it('keeps the memo apart per database', function (array $tenant) {
    config(['address.locality.country' => 'MYS']);
    Country::create(['code' => 'MYS', 'name' => 'Malaysia']);

    expect(Country::local()?->name)->toBe('Malaysia');

    config([
        'database.connections.tenant' => ['driver' => 'sqlite', 'database' => ':memory:', ...$tenant],
        'database.default' => 'tenant',
    ]);
    (include __DIR__.'/../../database/migrations/create_countries_table.php.stub')->up();
    DB::table('countries')->insert(['code' => 'MYS', 'name' => 'Malaysia Tenant', 'created_at' => now(), 'updated_at' => now()]);

    expect(Country::local()?->name)->toBe('Malaysia Tenant');
})->with([
    'another table prefix' => [['prefix' => 'tenant_']],
    'another host' => [['host' => 'shard-2']],
]);

it('does not keep a row read during the save', function () {
    config(['address.locality.country' => 'MYS']);
    $mys = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    Country::updating(function () {
        Country::local();
    });

    $mys->update(['name' => 'Malaysia Baru']);

    expect(Country::local()?->name)->toBe('Malaysia Baru');
});

it('sees its own change inside a transaction', function () {
    config(['address.locality.country' => 'MYS']);
    $mys = Country::create(['code' => 'MYS', 'name' => 'Malaysia']);
    Country::local();

    DB::transaction(function () use ($mys) {
        $mys->update(['name' => 'Malaysia Baru']);

        expect(Country::local()?->name)->toBe('Malaysia Baru');
    });
});

it('runs no query for repeated calls inside a transaction', function () {
    config(['address.locality.country' => 'MYS']);
    Country::create(['code' => 'MYS', 'name' => 'Malaysia']);

    DB::transaction(function () {
        Country::local();
        DB::enableQueryLog();

        foreach (range(1, 100) as $call) {
            Country::local();
        }

        expect(DB::getQueryLog())->toBe([]);
    });
});

it('binds integer locality codes as strings', function () {
    config(['address.locality.country' => 'MYS', 'address.locality.state' => 10, 'address.locality.district' => 2]);

    expect(State::query()->local()->getBindings())->toBe(['10', 'MYS'])
        ->and(District::query()->local()->getBindings())->toBe(['2', '10', 'MYS']);
});

it('runs no query when a locality code is not set', function () {
    config(['address.locality.country' => 'MYS', 'address.locality.state' => null, 'address.locality.district' => '02']);
    DB::enableQueryLog();

    expect(State::local())->toBeNull()
        ->and(District::local())->toBeNull()
        ->and(DB::getQueryLog())->toBe([]);
});
