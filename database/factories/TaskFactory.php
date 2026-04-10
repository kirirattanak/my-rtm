<?php

namespace Database\Factories;

use App\Enums\BrPriority;
use App\Enums\EffortUnit;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id'      => Project::factory(),
            'sprint_id'       => null,
            'title'           => fake()->sentence(5),
            'description'     => fake()->paragraph(),
            'effort_estimate' => null,
            'effort_unit'     => EffortUnit::Points->value,
            'status'          => TaskStatus::Todo->value,
            'priority'        => BrPriority::Medium->value,
            'category'        => null,
            'due_date'        => null,
            'start_date'      => null,
            'end_date'        => null,
            'assignee_id'     => null,
            'created_by'      => User::factory(),
            'completed_at'    => null,
        ];
    }
}
