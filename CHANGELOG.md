# Changelog

All notable changes to `laravel-address` will be documented in this file.

## v5.0.0

### Breaking

- Laravel 13.35 or later is now required. Laravel 12 is no longer supported
- `address(string|UnitEnum $type = 'primary')` replaces `getAddress()`, and `Addressable` changes to match. `$user->address` still returns the primary address, and `$user->address('billing')->first()` returns another type
- `render()` is removed. Use `lines()` in a Blade loop, or `formatted()`
- `formatted()` takes `$separator` as its first parameter
- Postcode and town are now one part, joined by a space: `40000 Shah Alam`
- `addressable_type` and `addressable_id` are no longer mass assignable. Set the owner through the relation
- `Address` relations now include soft-deleted places
- `local()` now keeps its result for one request or queue job. The shared cache, its keys, and `localityDescendants()` are removed. A model that uses `HasLocality` directly must implement `localityLevels()`
- Postcodes move to a `Postcode` model. `post_offices.postcodes` is gone, and `$postOffice->postcodes` is a `HasMany`. The data files keep their shape
- `address:seed` reads from and publishes to `database/data/address` instead of `data`
- `address:seed` no longer fires model events, and the protected `SeedCommand::seed()` has a new signature
- The migrations changed: new `addresses.district_id` and `addresses.subdistrict_id` columns, a new `postcodes` table, and a new unique index on `post_offices (state_id, name)`. The index also covers soft-deleted rows, so an app restores a trashed post office instead of creating a new one with the same name
- The bundled post office data is refreshed from AsyrafHussin/malaysia-postcodes: 16 invalid postcodes are removed and 5 are added; 5 towns are renamed, 1 is merged into another, 2 are removed, and 5 are added

### Added

- `Address::lines()`, which returns the non-empty parts of an address as a list
- `Address::district()` and `Address::subdistrict()`
- The `Postcode` model, with nullable `latitude` and `longitude`, and a `code` that is squished and uppercased
- `address()` accepts an enum as the address type
- `data/NOTICE.md`, which lists the data sources and ships the MIT notice of the postcode data

### Fixed

- Saves of `Country`, `State`, and `District` no longer use the cache store, so a cache outage no longer breaks them
- `local()` no longer uses a shared cache, so a rollback or another request cannot leave a stale row. A `local()` call during a save no longer keeps the old row
- A subclass and its base model now share one `local()` result, and two tenant databases no longer do
- `ofType()` now qualifies its column, so it works in joins, and binds enum values as strings, so an int-backed enum no longer matches every type on MySQL
- `formatted()` no longer keeps parts that are empty after cleaning
- A line or postcode that is blank after normalizing is stored as `null`, `postcode` is squished and uppercased, and a line no longer keeps a space before a stripped comma: `"No. 1 ,"` is stored as `"No. 1"`
- `address:seed` runs in one transaction, so a failure writes nothing. It compares each level with the database first, so a re-seed of unchanged data takes 6 reads and writes nothing, `updated_at` keeps its meaning, and no ids are used up. A first seed takes 33 queries instead of about 2,000
- `address:seed` and the `Address` relations work for configured models without `SoftDeletes`
- `address:seed` keeps columns that an app adds, accepts rows without optional keys, keeps the last of two rows with the same key, names the file when a row has no key, and names the row when the database merges two keys
- Locality codes written as integers now match the same rows on every database
- `local()` runs no query when the code of its level or of a parent level is `null`

### Changed

- CI runs on MySQL 8.4, MariaDB 11.4, and PostgreSQL 17 next to SQLite, with foreign keys on, and no longer runs Laravel 12 jobs
- `address:seed` prints the rows read and written per level
- `SECURITY.md` points to GitHub's private vulnerability reporting
- `PostOfficeFactory` draws unique names

### Removed

- `.github/FUNDING.yml`

## v4.1.0

### Security

- `Address::render()` now escapes every value with `e()` in block mode. It inserted address lines and names into the HTML as-is, so markup stored in an address ran wherever the result was output with `{!! !!}`. Inline mode still returns plain text

### Fixed

- The `Address` `saving` hook now normalizes only the line fields that changed and are not `null`. It rewrote all three lines on every save, so:
  - saving a partially selected address no longer blanks the unselected lines (or throws `MissingAttributeException` under `Model::preventAccessingMissingAttributes()`)
  - saving an unchanged address no longer issues an `UPDATE`
  - `null` lines stay `null` instead of being stored as `''`
  - the lines hold plain strings instead of `Stringable` objects, so `formatted()` right after `create()` no longer returns empty segments (`'No. 1, , , 40000'`)
- `render()` no longer strips trailing `p`, `<`, `/` or `>` characters from the last line (`Kukup` rendered as `Kuku`), drops parts that are empty after trimming (a postcode of `','` rendered as `', Kukup'`), and returns `''` instead of `'</p>'` for an address without parts
- `formatted(capitalize: true)` and `render(capitalize: true)` now uppercase multibyte characters
- `copy()` no longer copies `deleted_at` or the loaded `addressable` relation, so a copy of a trashed address is no longer saved as trashed
- `address()` and `getAddress()` now set `type` on models made with `create()`, `make()`, or `save()` through the relation. `$user->getAddress('billing')->create([...])` no longer fails on the `type` NOT NULL constraint. `forceCreate()` sets it only on Laravel 13.35 and later, where the framework applies the relation's pending attributes
- `local()` now stores the locality codes with its cached snapshot and resolves the row again when the configured codes change. A config change no longer keeps returning the previous row from the cache on later requests, and a save still clears the entry whatever the config. Entries written by earlier versions are ignored, including the model objects cached by v4.0.0–v4.0.3, which rehydrated as a model with a `null` id and code
- `scopeLocal()` on `Country`, `State`, and `District` now qualifies the `code` column. `$country->districts()->local()` no longer fails with `ambiguous column name: code`
- `HasMany` and `HasManyThrough` relations on `Country`, `State`, and `District` now pass explicit foreign keys, so a subclass with another class name (e.g. `Nation extends Country`) keeps resolving `country_id`, `state_id`, and `district_id`
- `address:seed`:
  - no longer throws `LazyLoadingViolationException` under `Model::preventLazyLoading()` or `Model::shouldBeStrict()`
  - updates soft-deleted rows instead of failing on the unique index (or creating duplicate post offices); the rows stay soft-deleted. When a live row and a soft-deleted duplicate both exist, it updates the live row
  - reads `base_path('data')` only when it contains `countries.json`, instead of failing on any unrelated `data` directory
  - throws an error that names the file when a JSON file is invalid, instead of skipping the file or failing with a `TypeError`
  - no longer queries each state and district again to resolve its parent
- `CountryFactory` now generates codes that cannot collide with ISO alpha-3 codes, so it works after `address:seed`
- `StateFactory` now draws state names from the `ms_MY` Faker locale, so it works when `app.faker_locale` has no `state()` formatter (e.g. `en_GB`)
- Removed the `Address` entry from `extra.laravel.aliases`. It pointed to a facade that does not exist and replaced any `Address` alias defined by the application
- Fixed the publish tags in the README (`address-migrations`, `address-config`) and documented the `address-data` tag

### Changed

- Raised the `illuminate/contracts` floor to `^12.9`. `local()` uses `cache()->memo()`, which Laravel added in v12.9.0
- `Address` now casts `latitude` and `longitude` to `float`
- `address:seed` now follows the data files down from `countries.json` and reads each level only for the parents listed in the level above. Earlier versions also read the files of parent rows that existed only in the database. The protected per-level methods of `SeedCommand` are merged into `seed()`
- CI now runs the tests on pushes to `main`, on pull requests, and on changes to migration stubs and seed data, and runs PHPStan on `composer.json` changes
- Removed the unused `resources/views` directory, the empty `package-lock.json`, the completed `TODO.md`, and the `Workbench\App` autoload-dev entry
- Removed the 118 empty `subdistricts.json` placeholder files from the bundled data. `address:seed` still treats an empty file as having no rows, so data published by earlier versions keeps working

## v4.0.6

- Added Pest 5 to the `pestphp/pest`, `pestphp/pest-plugin-arch`, and `pestphp/pest-plugin-laravel` dev constraints (`^4.0||^5.0`). Laravel 13 now resolves Pest 5; Laravel 12 stays on Pest 4, because Pest 5 requires `symfony/process` `^8.1` and PHPUnit 13 — Laravel 12 pins `symfony/process` to `^7.2`, and testbench 10 conflicts with PHPUnit `>=13.2`
- Dropped Pest 3 from the same constraints. Under `prefer-lowest` on Laravel 12 it resolved alongside testbench 10.0.0, whose bundled `config/database.php` reads `PDO::MYSQL_ATTR_SSL_CA` — deprecated in PHP 8.5, so every test in the PHP 8.5 / Laravel 12 / `prefer-lowest` CI job was reported as deprecated

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
