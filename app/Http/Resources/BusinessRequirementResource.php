<?php

namespace App\Http\Resources;

use App\Models\BusinessRequirement;

class BusinessRequirementResource
{
    public static function list(BusinessRequirement $br): array
    {
        return [
            'id'             => $br->id,
            'ref'            => $br->ref,
            'number'         => $br->number,
            'title'          => $br->title,
            'priority'       => $br->priority->value,
            'priority_label' => $br->priority->label(),
            'priority_color' => $br->priority->color(),
            'status'         => $br->status->value,
            'status_label'   => $br->status->label(),
            'status_color'   => $br->status->color(),
            'category'       => $br->category,
            'creator'        => $br->creator,
            'tr_count'       => $br->technical_requirements_count,
            'created_at'     => $br->created_at,
        ];
    }

    public static function detail(BusinessRequirement $br): array
    {
        return [
            'id'             => $br->id,
            'ref'            => $br->ref,
            'number'         => $br->number,
            'title'          => $br->title,
            'description'    => $br->description,
            'priority'       => $br->priority->value,
            'priority_label' => $br->priority->label(),
            'priority_color' => $br->priority->color(),
            'status'         => $br->status->value,
            'status_label'   => $br->status->label(),
            'status_color'   => $br->status->color(),
            'category'       => $br->category,
            'tags'           => $br->tags ?? [],
            'creator'        => $br->creator,
            'created_at'     => $br->created_at,
            'updated_at'     => $br->updated_at,
            'technical_requirements' => $br->technicalRequirements->map(
                fn ($tr) => TechnicalRequirementResource::linkedItem($tr)
            ),
            'comments' => $br->comments->map(fn ($c) => [
                'id'         => $c->id,
                'body'       => $c->body,
                'user'       => $c->user,
                'created_at' => $c->created_at,
            ]),
        ];
    }

    public static function linkedItem(BusinessRequirement $br): array
    {
        return [
            'id'             => $br->id,
            'ref'            => $br->ref,
            'title'          => $br->title,
            'status'         => $br->status->value,
            'status_label'   => $br->status->label(),
            'status_color'   => $br->status->color(),
            'priority'       => $br->priority->value,
            'priority_label' => $br->priority->label(),
            'priority_color' => $br->priority->color(),
        ];
    }
}
