# TODO

## v3.0 (breaking)

- [x] Add unique constraints on code columns:
  - `unique(code)` on countries
  - `unique(country_id, code)` on states
  - `unique(state_id, code)` on districts
  - `unique(district_id, code)` on subdistricts
- [x] Stop instantiating models inside migration stubs. Replaced `foreignIdFor($model)->constrained(new $model()->getTable())` with `foreignId($column)->references('id')->on(config('address.tables.…'))` across `states`, `post_offices`, `districts`, `subdistricts`, `addresses`. Avoids early-boot of the address models during `getEnvironmentSetUp` (before Eloquent's event dispatcher is wired).
- [x] Drop the `local`/`is_local` column from countries, states, districts. Locality is now config-driven — `Country::local()` / `State::local()` / `District::local()` resolve the matching row by `config('address.locality.{country,state,district}')` and the parent chain, returning `?static`.
- [x] Add `Hasyirin\Address\Contracts\Localizable` interface and `Hasyirin\Address\Concerns\HasLocality` trait. Trait declares `scopeLocal()` abstract and provides the static `local()` lookup. Each model implements `scopeLocal()` with `where('code', config(...))` plus `whereHas` for parent-chain enforcement.
- [x] Apply `HasLocality` trait + `Localizable` contract to `Country`, `State`, `District`. Lets callers compose: `District::query()->local()->where(...)->get()`.
- [x] Simplify `SeedCommand` — drop all locality writes; the seed just upserts rows by their natural keys. Locality is resolved at query time from config.
