<?php

namespace Hasyirin\Address\Database\Factories;

use Hasyirin\Address\Models\PostOffice;
use Hasyirin\Address\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostOffice>
 */
class PostOfficeFactory extends Factory
{
    public function modelName(): string
    {
        return config('address.models.post-office', PostOffice::class);
    }

    public function definition(): array
    {
        return [
            'state_id' => State::factory(),
            'name' => fake()->city(),
            'postcodes' => [fake()->postcode()],
        ];
    }
}
