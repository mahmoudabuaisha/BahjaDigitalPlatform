<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Child> */
class ChildFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake('ar_SA')->firstName(),
            'birth_date' => fake()->dateTimeBetween('-12 years', '-4 years'),
            'gender' => fake()->randomElement(['male', 'female']),
        ];
    }
}
