<?php

namespace Hasyirin\Address\Database\Factories;

use Hasyirin\Address\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    public function modelName(): string
    {
        return config('address.models.address', Address::class);
    }

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['primary', 'billing', 'shipping']),
            'line_1' => fake()->buildingNumber(),
            'line_2' => fake()->optional()->address(),
            'line_3' => null,
            'postcode' => fake()->postcode(),
            'latitude' => fake()->optional()->latitude(),
            'longitude' => fake()->optional()->longitude(),
            'properties' => [],
        ];
    }
}
