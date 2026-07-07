<?php

namespace Hasyirin\Address\Database\Factories;

use Hasyirin\Address\Models\District;
use Hasyirin\Address\Models\Subdistrict;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subdistrict>
 */
class SubdistrictFactory extends Factory
{
    public function modelName(): string
    {
        return config('address.models.subdistrict', Subdistrict::class);
    }

    public function definition(): array
    {
        return [
            'district_id' => District::factory(),
            'code' => fake()->unique()->numerify('####'),
            'name' => fake()->city(),
        ];
    }
}
