<?php

namespace Database\Factories;

use App\Enums\RequirementStatus;
use App\Enums\TrType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TechnicalRequirementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'number'      => fake()->unique()->numberBetween(1, 999),
            'title'       => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'type'        => TrType::Functional->value,
            'status'      => RequirementStatus::Draft->value,
            'created_by'  => User::factory(),
        ];
    }
}
