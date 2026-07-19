# Changelog

All notable changes to `laravel-address` will be documented in this file.

## v4.0.5

- Fixed `Country::local()` / `State::local()` / `District::local()` rehydrating with a `null` connection name, which silently broke `$model->is(Country::local())` identity checks (`Model::is()` compares primary key, table, *and* connection name). Introduced in v4.0.4: `HasLocality::local()` rehydrates via `newFromBuilder($attributes)` on `static::query()->getModel()` — the query builder's own template model, which (unlike a normally-queried/hydrated instance) is never routed through Eloquent's `newModelInstance()`, so its `getConnectionName()` returns `null` on any model without an explicit `$connection` override. `newFromBuilder()`'s implicit `$connection` fallback then carried that `null` through. It now resolves the connection via `$model->getConnection()->getName()` instead of `getConnectionName()` — this resolves the app's actual default connection rather than returning the unset override, matching what a normally-queried instance reports.

## v4.0.4

- Fixed `Country::local()` / `State::local()` / `District::local()` throwing a `__PHP_Incomplete_Class` type error under a serializing cache store such as redis. `HasLocality::local()` cached the resolved Eloquent model via `cache()->memo()->rememberForever(...)`; a persistent store serializes the model and hands it back as an incomplete class on a later request, violating the `?static` return type. It now caches the row's raw attributes and rehydrates with `newFromBuilder()`, preserving the cross-request cache while storing only serialization-safe scalars. The defect was masked by the `array` cache store used in the test suite, which holds live objects and never serializes

## v4.0.3

- Fixed intermittent unique-constraint failures when generating records through the model factories. `CountryFactory` now draws `code` via `fake()->unique()->countryISOAlpha3()`, so `Country::factory()->count($n)->create()` no longer collides on the global `countries.code` unique index — previously a birthday-paradox failure over Faker's fixed ~249-value ISO alpha-3 pool (~16% at 10 rows, ~99.9% at 60)
- Applied the same guard to `StateFactory`, `DistrictFactory`, and `SubdistrictFactory`, generating `code` via `fake()->unique()->numerify('####')`. Batches of children sharing one parent (e.g. `State::factory()->count($n)->create(['country_id' => $id])`) no longer collide on the per-parent `(parent_id, code)` unique index; the 4-digit width keeps ample headroom since `unique()` buckets its used-set by formatter name
- Replaced the placeholder `composer.json` description with a real one

## v4.0.2

- Replaced `__construct` overrides with `getTable()` overrides on `Address`, `Country`, `District`, `PostOffice`, `State`, and `Subdistrict` — `return $this->table ?? config('address.tables.X', parent::getTable())`. Subclasses that set `protected $table = '...'` are now respected instead of being overwritten by the parent constructor.

## v4.0.1

- Tightened `phpstan/phpstan-deprecation-rules` and `phpstan/phpstan-phpunit` dev constraints to `^2.0` — `larastan: ^3.0` already pins PHPStan 2, so the `^1.x` legs were unreachable

## v4.0.0

### Breaking changes

- Dropped Laravel 11 support — minimum is now Laravel 12. `illuminate/contracts` requires `^12.0||^13.0`; dev `orchestra/testbench` requires `^10.0||^11.0`. CI matrix Laravel 11 row removed.

### Added

- `local()` is now memoized per request via `cache()->memo()->rememberForever(...)` keyed by `'address.locality.{Country|State|District}'`
- `HasLocality::bootHasLocality()` registers `saved`/`deleted` listeners that call `clearLocalCache()` so mutations invalidate the memo within the same request
- Cross-class invalidation: each model declares its `localityDescendants()`; `clearLocalCache()` cascades down the chain, so saving a `Country` clears `State` and `District` caches in the same request without registering listeners on ancestor classes
- Public `clearLocalCache()` on each model for manual invalidation

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
