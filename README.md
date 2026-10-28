# Laravel Address

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hasyirin/laravel-address.svg?style=flat-square)](https://packagist.org/packages/hasyirin/laravel-address)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/hasyirin/laravel-address/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/hasyirin/laravel-address/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/hasyirin/laravel-address/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/hasyirin/laravel-address/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/hasyirin/laravel-address.svg?style=flat-square)](https://packagist.org/packages/hasyirin/laravel-address)

A Laravel package for managing addresses with polymorphic relationships and hierarchical geographical data (countries, states, districts, subdistricts, and post offices).

Comes with built-in geographical data for Malaysia and a seeder command to populate reference tables.

## Requirements

- PHP 8.4+
- Laravel 12.9+ or 13

## Installation

Install the package via Composer:

```bash
composer require hasyirin/laravel-address
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="address-migrations"
php artisan migrate
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="address-config"
```

## Seeding Geographical Data

Seed countries, states, districts, subdistricts, and post offices:

```bash
php artisan address:seed
```

The command matches existing rows by code (post offices by name), so you can run it again to apply data updates. Soft-deleted rows are updated but stay deleted. It follows the files down from `countries.json` and reads each level only for the parents listed in the level above.

The bundled subdistrict data is partial: it covers Negeri Sembilan and the Kemaman district of Terengganu.

To use your own data, publish the bundled files and edit them:

```bash
php artisan vendor:publish --tag="address-data"
```

The command reads from `base_path('data')` when that directory contains `countries.json`. Otherwise, it uses the package's bundled data.

## Usage

### Preparing Your Model

Add the `InteractsWithAddresses` trait to any model that should have addresses:

```php
use Hasyirin\Address\Concerns\InteractsWithAddresses;
use Hasyirin\Address\Contracts\Addressable;

class User extends Model implements Addressable
{
    use InteractsWithAddresses;
}
```

### Creating Addresses

The `address()` and `getAddress()` relations set `type` for you when you call `create()`, `make()`, or `save()` on them (`forceCreate()` needs Laravel 13.35 or later):

```php
$user->address()->create([
    'line_1' => '123 Main Street',
    'line_2' => 'Suite 4B',
    'line_3' => 'Taman Example',
    'postcode' => '50000',
    'country_id' => $country->id,
    'state_id' => $state->id,
    'post_office_id' => $postOffice->id,
    'latitude' => 3.1390,
    'longitude' => 101.6869,
    'properties' => ['notes' => 'Front gate access'],
]);

$user->getAddress('billing')->create([
    'line_1' => '456 Side Street',
    'postcode' => '50000',
]);
```

### Using the Factory

The `Address` model includes a factory for testing:

```php
use Hasyirin\Address\Models\Address;

Address::factory()
    ->for($user, 'addressable')
    ->create([
        'type' => 'primary',
        'line_1' => 'No. 1, Jalan Test',
        'postcode' => '40000',
    ]);
```

### Retrieving Addresses

```php
// Primary address (returns a default empty instance if none exists)
$user->address;

// All addresses
$user->addresses;

// Address by type
$user->getAddress('billing')->first();
```

### Querying by Type

The `ofType` scope accepts a string, array, or enum:

```php
use Hasyirin\Address\Models\Address;

Address::ofType('shipping')->get();
Address::ofType(['billing', 'shipping'])->get();
```

### Formatting

```php
// Single-line comma-separated string
$address->formatted();
// "123 Main Street, Suite 4B, Taman Example, 50000, Kuala Lumpur, Selangor, Malaysia"

// Exclude state or country
$address->formatted(state: false);
$address->formatted(country: false);

// Uppercase
$address->formatted(capitalize: true);
```

### HTML Rendering

```php
// Multi-line with <p> tags
$address->render();

// Inline comma-separated
$address->render(inline: true);

// With options
$address->render(state: false, country: false, capitalize: true, margin: 1);
```

The block output is HTML with every value escaped, so you can output it in Blade with `{!! $address->render() !!}`. The inline output and `formatted()` are plain text; output them with `{{ }}`.

### Copying an Address

Create an unsaved copy of an address (without the polymorphic relationship, type, or `deleted_at`):

```php
$copy = $address->copy();
$copy->type = 'billing';
$copy->addressable()->associate($anotherModel);
$copy->save();
```

## Locality

Designate a "local" country, state, and district by setting their codes in `config/address.php`:

```php
'locality' => [
    'country' => 'MYS',
    'state' => '14',
    'district' => '02',
],
```

Resolve them anywhere via the static helper — it enforces the parent chain, so a state only counts as local if its country also matches the configured code:

```php
Country::local();   // ?Country
State::local();     // ?State — matches state code AND parent country
District::local();  // ?District — matches district code AND parent state AND parent country
```

If any level is null in the config, the corresponding `local()` returns `null`.

Compose with the `local()` query scope:

```php
District::query()->local()->where(...)->get();
```

The result is cached forever in your default cache store and memoized per request via `cache()->memo()`. The cached entry records the locality codes it was resolved for, so a config change takes effect immediately. A miss is never cached. `saved` and `deleted` listeners on each model cascade-clear caches down the chain (`Country` save clears `State` and `District` caches too). Writes that skip model events, such as query builder updates, `upsert()`, or `saveQuietly()`, do not clear the cache. For manual invalidation:

```php
Country::clearLocalCache();
```

## Configuration

```php
// config/address.php

return [
    // Codes used to resolve `Country::local()`, `State::local()`, `District::local()`
    'locality' => [
        'country' => null,  // e.g. 'MYS'
        'state' => null,    // e.g. '14'
        'district' => null, // e.g. '02'
    ],

    // Override model classes
    'models' => [
        'address' => \Hasyirin\Address\Models\Address::class,
        'country' => \Hasyirin\Address\Models\Country::class,
        'state' => \Hasyirin\Address\Models\State::class,
        'district' => \Hasyirin\Address\Models\District::class,
        'subdistrict' => \Hasyirin\Address\Models\Subdistrict::class,
        'post-office' => \Hasyirin\Address\Models\PostOffice::class,
    ],

    // Override table names
    'tables' => [
        'addresses' => 'addresses',
        'countries' => 'countries',
        'states' => 'states',
        'districts' => 'districts',
        'subdistricts' => 'subdistricts',
        'post_offices' => 'post_offices',
    ],
];
```

## Data Structure

The package uses a hierarchical location model:

```
Country
 └── State
      ├── District
      │    └── Subdistrict
      └── Post Office (with postcodes)
```

Each `Address` optionally belongs to a `Country`, `State`, and `PostOffice`, and is polymorphically attached to any model via the `addressable` morph relationship.

## Models

| Model         | Key Fields                                                                          |
|---------------|-------------------------------------------------------------------------------------|
| `Country`     | `code` (ISO 3166-1 alpha-3), `alpha_2`, `name`                                      |
| `State`       | `code`, `name`, belongs to `Country`                                                |
| `District`    | `code`, `name`, belongs to `State`                                                  |
| `Subdistrict` | `code`, `name`, belongs to `District`                                               |
| `PostOffice`  | `name`, `postcodes` (JSON array), belongs to `State`                                |
| `Address`     | `type`, `line_1`-`line_3`, `postcode`, `latitude`, `longitude`, `properties` (JSON) |

All models use soft deletes.

## Extending Models

To use your own model classes, extend the package models and update the config:

```php
use Hasyirin\Address\Models\Address as BaseAddress;

class Address extends BaseAddress
{
    // your customizations
}
```

```php
// config/address.php
'models' => [
    'address' => \App\Models\Address::class,
    // ...
],
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Hasyirin Fakhriy](https://github.com/hasyirin)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
