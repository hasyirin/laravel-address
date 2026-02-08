# Changelog

All notable changes to `laravel-address` will be documented in this file.

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
