<?php

namespace App\Http\Resources;

use App\Models\TestCase;

class TestCaseResource
{
    public static function list(TestCase $tc): array
    {
        return [
            'id'             => $tc->id,
            'ref'            => $tc->ref,
            'number'         => $tc->number,
            'title'          => $tc->title,
            'type'           => $tc->type->value,
            'type_label'     => $tc->type->label(),
            'priority'       => $tc->priority->value,
            'priority_label' => $tc->priority->label(),
            'status'         => $tc->status->value,
            'status_label'   => $tc->status->label(),
            'status_color'   => $tc->status->color(),
            'assignee'       => $tc->assignee,
            'creator'        => $tc->creator,
            'runs_count'     => $tc->runs_count,
            'latest_run'     => $tc->runs->first()?->status,
            'created_at'     => $tc->created_at,
        ];
    }

    public static function detail(TestCase $tc): array
    {
        return [
            'id'              => $tc->id,
            'ref'             => $tc->ref,
            'number'          => $tc->number,
            'title'           => $tc->title,
            'description'     => $tc->description,
            'steps'           => $tc->steps ?? [],
            'expected_result' => $tc->expected_result,
            'type'            => $tc->type->value,
            'type_label'      => $tc->type->label(),
            'priority'        => $tc->priority->value,
            'priority_label'  => $tc->priority->label(),
            'status'          => $tc->status->value,
            'status_label'    => $tc->status->label(),
            'status_color'    => $tc->status->color(),
            'assignee'        => $tc->assignee,
            'creator'         => $tc->creator,
            'created_at'      => $tc->created_at,
            'updated_at'      => $tc->updated_at,
            'technical_requirements' => $tc->technicalRequirements->map(
                fn ($tr) => TechnicalRequirementResource::linkedItem($tr)
            ),
            'runs' => $tc->runs->map(fn ($r) => [
                'id'           => $r->id,
                'status'       => $r->status->value,
                'status_label' => $r->status->label(),
                'color'        => $r->status->color(),
                'notes'        => $r->notes,
                'executor'     => $r->executor,
                'created_at'   => $r->created_at,
            ]),
        ];
    }

    public static function linkedItem(TestCase $tc): array
    {
        return [
            'id'             => $tc->id,
            'ref'            => $tc->ref,
            'title'          => $tc->title,
            'type'           => $tc->type->value,
            'type_label'     => $tc->type->label(),
            'priority'       => $tc->priority->value,
            'priority_label' => $tc->priority->label(),
            'status'         => $tc->status->value,
            'status_label'   => $tc->status->label(),
            'status_color'   => $tc->status->color(),
            'assignee'       => $tc->assignee,
            'latest_run'     => $tc->runs->first()?->status?->value,
        ];
    }
}
