<?php

namespace Database\Factories;

use App\Enums\PlaceStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Place;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Place>
 */
class PlaceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'address_text' => fake()->address(),
            'google_place_id' => fake()->unique()->numerify('Ch################'),
            'phone' => fake()->phoneNumber(),
            'website_url' => null,
            'google_maps_url' => 'https://maps.google.com/?q='.fake()->latitude().','.fake()->longitude(),
            'district_id' => District::factory(),
            'category_id' => Category::factory(),
            'latitude' => fake()->latitude(20.9, 21.2),
            'longitude' => fake()->longitude(105.7, 106.0),
            'min_price' => null,
            'max_price' => null,
            'rating' => 5.0,
            'description' => null,
            'thumbnail_image_id' => null,
            'status' => PlaceStatus::Active,
            'is_verified' => true,
            'created_by' => null,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlaceStatus::Hidden,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
        ]);
    }

    public function rating(float $rating): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $rating,
        ]);
    }
}
