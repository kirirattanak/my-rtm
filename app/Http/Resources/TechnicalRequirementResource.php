<?php

namespace App\Http\Resources;

use App\Models\TechnicalRequirement;

class TechnicalRequirementResource
{
    public static function list(TechnicalRequirement $tr): array
    {
        return [
            'id'           => $tr->id,
            'ref'          => $tr->ref,
            'number'       => $tr->number,
            'title'        => $tr->title,
            'type'         => $tr->type->value,
            'type_label'   => $tr->type->label(),
            'status'       => $tr->status->value,
            'status_label' => $tr->status->label(),
            'status_color' => $tr->status->color(),
            'creator'      => $tr->creator,
            'br_count'     => $tr->business_requirements_count,
            'created_at'   => $tr->created_at,
        ];
    }

    public static function detail(TechnicalRequirement $tr): array
    {
        return [
            'id'           => $tr->id,
            'ref'          => $tr->ref,
            'number'       => $tr->number,
            'title'        => $tr->title,
            'description'  => $tr->description,
            'type'         => $tr->type->value,
            'type_label'   => $tr->type->label(),
            'status'       => $tr->status->value,
            'status_label' => $tr->status->label(),
            'status_color' => $tr->status->color(),
            'creator'      => $tr->creator,
            'created_at'   => $tr->created_at,
            'updated_at'   => $tr->updated_at,
            'business_requirements' => $tr->businessRequirements->map(
                fn ($br) => BusinessRequirementResource::linkedItem($br)
            ),
            'test_cases' => $tr->testCases->map(fn ($tc) => TestCaseResource::linkedItem($tc)),
            'comments' => $tr->comments->map(fn ($c) => [
                'id'         => $c->id,
                'body'       => $c->body,
                'user'       => $c->user,
                'created_at' => $c->created_at,
            ]),
        ];
    }

    public static function linkedItem(TechnicalRequirement $tr): array
    {
        return [
            'id'           => $tr->id,
            'ref'          => $tr->ref,
            'title'        => $tr->title,
            'status'       => $tr->status->value,
            'status_label' => $tr->status->label(),
            'status_color' => $tr->status->color(),
            'type'         => $tr->type->value,
            'type_label'   => $tr->type->label(),
        ];
    }
}
