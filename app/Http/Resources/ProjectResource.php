<?php

namespace App\Http\Resources;

use App\Models\Project;

class ProjectResource
{
    public static function list(Project $project): array
    {
        return [
            'id'            => $project->id,
            'name'          => $project->name,
            'description'   => $project->description,
            'status'        => $project->status->value,
            'status_label'  => $project->status->label(),
            'status_color'  => $project->status->color(),
            'owner'         => $project->owner->name,
            'start_date'    => $project->start_date?->toDateString(),
            'target_date'   => $project->target_date?->toDateString(),
            'members_count' => $project->project_members_count,
        ];
    }
}
