<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('stores timestamps with the time zone', function (string $table) {
    $types = collect(Schema::getColumns(config("address.tables.$table")))
        ->whereIn('name', ['created_at', 'updated_at', 'deleted_at'])
        ->pluck('type_name', 'name')
        ->all();

    expect($types)->toBe([
        'created_at' => 'timestamptz',
        'updated_at' => 'timestamptz',
        'deleted_at' => 'timestamptz',
    ]);
})->with(['addresses', 'countries', 'states', 'districts', 'subdistricts', 'post_offices', 'postcodes'])
    ->skip(fn () => DB::getDriverName() !== 'pgsql', 'Only PostgreSQL stores a different column type for timestampsTz().');
