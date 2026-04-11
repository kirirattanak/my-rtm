<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class SprintFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+1 month');

        return [
            'project_id' => Project::factory(),
            'name'       => 'Sprint ' . fake()->numberBetween(1, 20),
            'start_date' => $start->format('Y-m-d'),
            'end_date'   => date('Y-m-d', strtotime('+14 days', $start->getTimestamp())),
            'capacity'   => null,
        ];
    }
}
