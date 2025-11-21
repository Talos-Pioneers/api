<?php

namespace Database\Factories;

use App\Models\Blueprint;
use App\Models\BlueprintCollection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reportableType = $this->faker->randomElement([
            Blueprint::class,
            BlueprintCollection::class,
        ]);

        return [
            'user_id' => User::factory(),
            'reportable_type' => $reportableType,
            'reportable_id' => function (array $attributes) {
                if ($attributes['reportable_type'] === Blueprint::class) {
                    return Blueprint::factory()->create()->id;
                }

                return BlueprintCollection::factory()->create()->id;
            },
            'reason' => $this->faker->optional()->sentence(),
        ];
    }
}
