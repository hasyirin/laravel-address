# Changelog

All notable changes to `laravel-address` will be documented in this file.

## v2.4.0

- Added Laravel 13 support — widened `illuminate/contracts` to `^11.0||^12.0||^13.0`
- Widened `orchestra/testbench` dev requirement to `^9.0.0||^10.0||^11.0`
- Added Laravel 13 / testbench 11 row to the CI matrix
- Updated `AddressFactory` to use `buildingNumber()` for `line_1` and `address()` for `line_2`

## v2.3.0

- Renamed published data files from `mukims.json` to `subdistricts.json` across `data/MYS/states/*/districts/*/`

## v2.2.0

- Added `local` boolean column to `districts` table migration
- Added `local` attribute, cast, and `District::local()` static method to retrieve the local district
- Added `district` locality config option in `address.locality`
- Added publishable `data` directory via `address-data` publish tag
- Updated `SeedCommand` to set `local` flag on districts based on locality config

## v2.1.0

- Added `AddressFactory` with `HasFactory` support on the `Address` model
- Refactored `copy()` to use `replicate()` instead of `make()`
- Updated README with Laravel 12 requirement, factory usage docs, and config example fix
