<?php

namespace Hasyirin\Address\Tests;

use Hasyirin\Address\AddressServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            AddressServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Load the package config before migrations run (migrations reference config values)
        config()->set('address', require __DIR__.'/../config/address.php');

        // Create a users table for polymorphic relationship testing
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Run the migration stubs in dependency order
        $migrations = [
            'create_countries_table',
            'create_states_table',
            'create_post_offices_table',
            'create_districts_table',
            'create_subdistricts_table',
            'create_addresses_table',
        ];

        foreach ($migrations as $migration) {
            (include __DIR__."/../database/migrations/{$migration}.php.stub")->up();
        }
    }
}
