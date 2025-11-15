<?php

namespace Database\Factories;

use App\Enums\FacilityType;
use App\Enums\Locale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Facility>
 */
class FacilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'icon' => fake()->word(),
            'type' => fake()->randomElement(FacilityType::cases()),
            'range' => [
                'width' => fake()->numberBetween(1, 10),
                'height' => fake()->numberBetween(1, 10),
                'depth' => fake()->numberBetween(1, 10),
                'x' => 0,
                'y' => 0,
                'z' => 0,
            ],
            'name' => [
                Locale::ENGLISH->value => fake()->words(2, true),
            ],
            'description' => [
                Locale::ENGLISH->value => fake()->sentence(),
            ],
        ];
    }
}
