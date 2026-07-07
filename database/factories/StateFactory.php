<?php

namespace Hasyirin\Address\Database\Factories;

use Hasyirin\Address\Models\Country;
use Hasyirin\Address\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<State>
 */
class StateFactory extends Factory
{
    public function modelName(): string
    {
        return config('address.models.state', State::class);
    }

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'code' => fake()->unique()->numerify('####'),
            // @phpstan-ignore-next-line method.notFound
            'name' => fake()->state(),
        ];
    }
}
