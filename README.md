# Laravel Address

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hasyirin/laravel-address.svg?style=flat-square)](https://packagist.org/packages/hasyirin/laravel-address)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/hasyirin/laravel-address/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/hasyirin/laravel-address/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/hasyirin/laravel-address/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/hasyirin/laravel-address/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/hasyirin/laravel-address.svg?style=flat-square)](https://packagist.org/packages/hasyirin/laravel-address)

A Laravel package for managing addresses with polymorphic relationships and hierarchical geographical data (countries, states, districts, subdistricts, and post offices).

Comes with built-in geographical data for Malaysia and a seeder command to populate reference tables.

## Requirements

- PHP 8.4+
- Laravel 10 or 11

## Installation

Install the package via Composer:

```bash
composer require hasyirin/laravel-address
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="laravel-address-migrations"
php artisan migrate
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="laravel-address-config"
```

## Seeding Geographical Data

Seed countries, states, districts, subdistricts, and post offices:

```bash
php artisan address:seed
```

The command loads data from the package's `data/` directory. To use your own data, place JSON files in your application's `base_path('data')` directory following the same structure.

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

```php
$user->address()->create([
    'type' => 'primary',
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
```

### Retrieving Addresses

```php
// Primary address (returns a default empty instance if none exists)
$user->address;

// All addresses
$user->addresses;

// Address by type
$user->getAddress('billing');
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

### Copying an Address

Create an unsaved copy of an address (without the polymorphic relationship or type):

```php
$copy = $address->copy();
$copy->type = 'billing';
$copy->addressable()->associate($anotherModel);
$copy->save();
```

## Configuration

```php
// config/address.php

return [
    // Mark a country/state as "local" during seeding
    'locality' => [
        'country' => null, // e.g. 'MYS'
        'state' => null,   // e.g. 'SGR'
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

Each `Address` belongs to a `Country`, `State`, and optionally a `PostOffice`, and is polymorphically attached to any model via the `addressable` morph relationship.

## Models

| Model | Key Fields |
|---|---|
| `Country` | `code` (ISO 3166-1 alpha-3), `alpha_2`, `name`, `local` |
| `State` | `code`, `name`, `local`, belongs to `Country` |
| `District` | `code`, `name`, belongs to `State` |
| `Subdistrict` | `code`, `name`, belongs to `District` |
| `PostOffice` | `name`, `postcodes` (JSON array), belongs to `State` |
| `Address` | `type`, `line_1`-`line_3`, `postcode`, `latitude`, `longitude`, `properties` (JSON) |

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

## Credits

- [Hasyirin Fakhriy](https://github.com/hasyirin)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
