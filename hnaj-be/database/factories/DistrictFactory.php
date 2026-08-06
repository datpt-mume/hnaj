<?php

namespace Database\Factories;

use App\Enums\DistrictStatus;
use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'code' => null,
            'status' => DistrictStatus::Active,
        ];
    }
}
