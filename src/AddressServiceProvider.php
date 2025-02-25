<?php

namespace Hasyirin\Address;

use Hasyirin\Address\Commands\SeedCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AddressServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-address')
            ->hasConfigFile()
            ->hasMigrations([
                'create_countries_table',
                'create_states_table',
                'create_post_offices_table',
                'create_districts_table',
                'create_subdistricts_table',
                'create_addresses_table',
            ])->hasCommands([
                SeedCommand::class,
            ]);
    }
}
