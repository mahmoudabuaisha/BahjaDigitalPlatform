<?php

namespace Database\Factories;

use App\Enums\FeedbackSource;
use App\Models\Feedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feedback>
 */
class FeedbackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => null,
            'source' => FeedbackSource::Family,
            'rating' => fake()->numberBetween(3, 5),
            'message' => fake()->boolean(60) ? fake()->realText(80) : null,
            'contact_name' => fake()->boolean(40) ? fake()->name() : null,
        ];
    }
}
