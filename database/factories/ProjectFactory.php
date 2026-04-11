<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => fake()->words(3, true),
            'description' => fake()->sentence(),
            'status'      => ProjectStatus::Active->value,
            'owner_id'    => User::factory()->projectManager(),
            'start_date'  => null,
            'target_date' => null,
        ];
    }
}
