<?php

namespace Hasyirin\Address\Database\Factories;

use Hasyirin\Address\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    public function modelName(): string
    {
        return config('address.models.country', Country::class);
    }

    public function definition(): array
    {
        return [
            'code' => fake()->countryISOAlpha3(),
            'name' => fake()->country(),
            'alpha_2' => fake()->countryCode(),
        ];
    }
}
