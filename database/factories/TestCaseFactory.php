<?php

namespace Database\Factories;

use App\Enums\BrPriority;
use App\Enums\RequirementStatus;
use App\Enums\TestCaseType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestCaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id'      => Project::factory(),
            'number'          => fake()->unique()->numberBetween(1, 999),
            'title'           => fake()->sentence(5),
            'description'     => fake()->paragraph(),
            'steps'           => [],
            'expected_result' => fake()->sentence(),
            'type'            => TestCaseType::Manual->value,
            'priority'        => BrPriority::Medium->value,
            'status'          => RequirementStatus::Draft->value,
            'assignee_id'     => null,
            'created_by'      => User::factory(),
        ];
    }
}
