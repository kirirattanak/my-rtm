<?php

namespace App\Http\Resources;

use App\Models\Sprint;

class SprintResource
{
    public static function summary(Sprint $sprint): array
    {
        return [
            'id'          => $sprint->id,
            'name'        => $sprint->name,
            'start_date'  => $sprint->start_date->toDateString(),
            'end_date'    => $sprint->end_date->toDateString(),
            'capacity'    => $sprint->capacity,
            'is_active'   => $sprint->isActive(),
            'tasks_count' => $sprint->tasks_count,
        ];
    }
}
