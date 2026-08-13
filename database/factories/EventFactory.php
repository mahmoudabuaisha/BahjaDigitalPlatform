<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Area;
use App\Models\Category;
use App\Models\Event;
use App\Models\ShelterCenter;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $titles = [
            'عرض دمى متحركة',
            'يوم ترفيهي مفتوح',
            'ورشة رسم وتلوين',
            'مسرح تفاعلي للأطفال',
            'فقرة أناشيد وألعاب',
            'جلسة دعم نفسي بالحكايات',
            'مهرجان صغير للفرح',
            'ألعاب حركية وجوائز',
        ];

        $startHour = fake()->numberBetween(9, 16);

        return [
            'team_id' => Team::factory(),
            'category_id' => Category::query()->inRandomOrder()->value('id'),
            'area_id' => Area::query()->inRandomOrder()->value('id') ?? Area::factory(),
            'shelter_center_id' => null,
            'title' => fake()->randomElement($titles),
            'description' => fake()->realText(200),
            'location_details' => 'الساحة الرئيسية قرب المدخل',
            'start_date' => fake()->dateTimeBetween('now', '+14 days')->format('Y-m-d'),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:30', $startHour + 1),
            'status' => EventStatus::Approved,
            'expected_children' => fake()->numberBetween(30, 150),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Draft]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Approved]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => EventStatus::Completed,
            'start_date' => fake()->dateTimeBetween('-14 days', '-1 day')->format('Y-m-d'),
            'actual_children' => fake()->numberBetween(20, 180),
            'actual_caregivers' => fake()->numberBetween(10, 80),
        ]);
    }

    public function inCenter(ShelterCenter $center): static
    {
        return $this->state(fn () => [
            'shelter_center_id' => $center->id,
            'area_id' => $center->area_id,
        ]);
    }
}
