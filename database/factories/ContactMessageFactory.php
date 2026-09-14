<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->boolean(50) ? fake()->safeEmail() : null,
            'phone' => fake()->boolean(70) ? '059'.fake()->numerify('#######') : null,
            'subject' => fake()->boolean(60) ? fake()->realText(40) : null,
            'message' => fake()->realText(220),
        ];
    }

    public function handled(): static
    {
        return $this->state(fn (): array => [
            'handled_at' => now()->subDay(),
            'note' => 'رُدّ عبر واتساب',
        ]);
    }
}
