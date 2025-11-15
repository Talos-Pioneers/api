<?php

namespace Database\Factories;

use App\Enums\GameVersion;
use App\Enums\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Blueprint>
 */
class BlueprintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'code' => fake()->unique()->regexify('[A-Za-z0-9]{16}'),
            'title' => fake()->sentence(),
            'slug' => fake()->slug(),
            'version' => GameVersion::CBT_3,
            'description' => fake()->paragraph(),
            'status' => Status::DRAFT,
            'region' => null,
            'is_anonymous' => false,
        ];
    }
}
