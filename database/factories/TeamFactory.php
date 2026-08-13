<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'فريق '.fake()->unique()->firstName(),
            'slug' => 'team-'.Str::lower(Str::random(6)),
            'description' => fake()->realText(120),
            'contact_name' => fake()->name(),
            'whatsapp_phone' => '97059'.fake()->numerify('#######'),
            'is_active' => true,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
