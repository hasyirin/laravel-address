<?php

namespace Hasyirin\Address\Database\Factories;

use Hasyirin\Address\Models\Postcode;
use Hasyirin\Address\Models\PostOffice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Postcode>
 */
class PostcodeFactory extends Factory
{
    public function modelName(): string
    {
        return config('address.models.postcode', Postcode::class);
    }

    public function definition(): array
    {
        return [
            'post_office_id' => PostOffice::factory(),
            'code' => fake()->unique()->numerify('#####'),
        ];
    }
}
