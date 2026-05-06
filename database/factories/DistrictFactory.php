<?php

namespace Hasyirin\Address\Database\Factories;

use Hasyirin\Address\Models\District;
use Hasyirin\Address\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{
    public function modelName(): string
    {
        return config('address.models.district', District::class);
    }

    public function definition(): array
    {
        return [
            'state_id' => State::factory(),
            'code' => fake()->numerify('##'),
            'name' => fake()->city(),
        ];
    }
}
