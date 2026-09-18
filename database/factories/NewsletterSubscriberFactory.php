<?php

namespace Database\Factories;

use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'area_id' => null,
            'token' => NewsletterSubscriber::newToken(),
            'confirmed_at' => now()->subDays(3),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['confirmed_at' => null]);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (): array => ['unsubscribed_at' => now()->subDay()]);
    }
}
