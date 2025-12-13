<?php

namespace Database\Factories;

use App\Enums\ItemType;
use App\Enums\Locale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
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
            'type' => fake()->randomElement(ItemType::craftableTypes()),
            'output_facility_craft_table' => [],
            'name' => [
                Locale::ENGLISH->value => fake()->words(2, true),
            ],
            'description' => [
                Locale::ENGLISH->value => fake()->sentence(),
            ],
        ];
    }
}
