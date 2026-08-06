<?php

namespace Database\Factories;

use App\Enums\ScheduleType;
use App\Models\Place;
use App\Models\PlaceOpeningHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlaceOpeningHour>
 */
class PlaceOpeningHourFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'place_id' => Place::factory(),
            'day_of_week' => fake()->numberBetween(2, 8),
            'schedule_type' => ScheduleType::Regular,
            'opens_at' => '08:00',
            'closes_at' => '22:00',
        ];
    }

    public function regular(int $day, string $opens, string $closes): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $day,
            'schedule_type' => ScheduleType::Regular,
            'opens_at' => $opens,
            'closes_at' => $closes,
        ]);
    }

    public function allDay(int $day): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $day,
            'schedule_type' => ScheduleType::AllDay,
            'opens_at' => null,
            'closes_at' => null,
        ]);
    }

    public function closed(int $day): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $day,
            'schedule_type' => ScheduleType::Closed,
            'opens_at' => null,
            'closes_at' => null,
        ]);
    }
}
