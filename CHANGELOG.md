# Changelog

All notable changes to `laravel-address` will be documented in this file.

## v3.0.0

### Breaking changes

- Removed the `local` boolean column from `countries`, `states`, and `districts` tables (along with the cast, default, fillable entry, and `@property` PHPDoc)
- Removed `local()` factory state on `CountryFactory`, `StateFactory`, and `DistrictFactory`
- `Country::local()` / `State::local()` / `District::local()` now resolve from `config('address.locality.{country,state,district}')` and the parent chain instead of the column. Return type widened from `static` to `?static` — returns `null` when the relevant locality config is unset or no row matches
- `SeedCommand` no longer writes a locality flag; locality is now query-time only

### Added

- `Hasyirin\Address\Contracts\Localizable` interface declaring `scopeLocal(Builder)` and `static local(): ?static`
- `Hasyirin\Address\Concerns\HasLocality` trait with the abstract `scopeLocal()` and a shared static `local()` lookup
- `scopeLocal()` on `Country`, `State`, and `District` for query composition: `District::query()->local()->where(...)->get()`
- Unique constraints on code columns:
  - `unique(code)` on countries
  - `unique(country_id, code)` on states
  - `unique(state_id, code)` on districts
  - `unique(district_id, code)` on subdistricts

### Changed

- Migration stubs no longer instantiate models — `foreignIdFor($model)->constrained(new $model()->getTable())` replaced with `foreignId('column')->references('id')->on(config('address.tables.…'))` across `states`, `post_offices`, `districts`, `subdistricts`, and `addresses`

## v2.5.0

- Added `CountryFactory`, `StateFactory`, `DistrictFactory`, `SubdistrictFactory`, and `PostOfficeFactory`
- Added `local()` factory state on `CountryFactory`, `StateFactory`, and `DistrictFactory`
- Wired `HasFactory` trait and `newFactory()` resolver into `Country`, `State`, `District`, `Subdistrict`, and `PostOffice` models so the configured model class is used when overridden in `address.models`

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
