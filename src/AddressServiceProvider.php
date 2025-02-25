<?php

namespace Hasyirin\Address;

use Hasyirin\Address\Commands\AddressCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AddressServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-address')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_address_table')
            ->hasCommand(AddressCommand::class);
    }
}
