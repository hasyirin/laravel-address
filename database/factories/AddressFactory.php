<?php

namespace Hasyirin\Address\Database\Factories;

use Hasyirin\Address\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['primary', 'billing', 'shipping']),
            'line_1' => fake()->streetAddress(),
            'line_2' => fake()->optional()->secondaryAddress(),
            'line_3' => null,
            'postcode' => fake()->postcode(),
            'latitude' => fake()->optional()->latitude(),
            'longitude' => fake()->optional()->longitude(),
            'properties' => [],
        ];
    }
}
