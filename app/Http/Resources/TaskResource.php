<?php

namespace App\Http\Resources;

use App\Models\Task;

class TaskResource
{
    public static function list(Task $task): array
    {
        return [
            'id'                => $task->id,
            'title'             => $task->title,
            'status'            => $task->status->value,
            'status_label'      => $task->status->label(),
            'priority'          => $task->priority->value,
            'priority_label'    => $task->priority->label(),
            'category'          => $task->category?->value,
            'category_label'    => $task->category?->label(),
            'effort_estimate'   => $task->effort_estimate,
            'effort_unit'       => $task->effort_unit->value,
            'effort_unit_short' => $task->effort_unit->shortLabel(),
            'due_date'          => $task->due_date?->toDateString(),
            'start_date'        => $task->start_date?->toDateString(),
            'end_date'          => $task->end_date?->toDateString(),
            'assignee'          => $task->assignee ? ['id' => $task->assignee->id, 'name' => $task->assignee->name] : null,
            'sprint'            => $task->sprint ? ['id' => $task->sprint->id, 'name' => $task->sprint->name] : null,
        ];
    }

    public static function detail(Task $task): array
    {
        return [
            'id'                => $task->id,
            'title'             => $task->title,
            'description'       => $task->description,
            'status'            => $task->status->value,
            'status_label'      => $task->status->label(),
            'priority'          => $task->priority->value,
            'priority_label'    => $task->priority->label(),
            'category'          => $task->category?->value,
            'category_label'    => $task->category?->label(),
            'effort_estimate'   => $task->effort_estimate,
            'effort_unit'       => $task->effort_unit->value,
            'effort_unit_label' => $task->effort_unit->label(),
            'effort_unit_short' => $task->effort_unit->shortLabel(),
            'due_date'          => $task->due_date?->toDateString(),
            'start_date'        => $task->start_date?->toDateString(),
            'end_date'          => $task->end_date?->toDateString(),
            'assignee'          => $task->assignee ? ['id' => $task->assignee->id, 'name' => $task->assignee->name] : null,
            'sprint'            => $task->sprint ? ['id' => $task->sprint->id, 'name' => $task->sprint->name] : null,
            'creator'           => $task->creator,
            'created_at'        => $task->created_at->toDateString(),
            'completed_at'      => $task->completed_at?->toDateString(),
            'logged_hours'      => $task->totalLoggedHours(),
            'effective_actual'  => $task->effectiveActualHours(),
            'linked_brs'        => $task->linkedBrs->map(fn ($br) => ['id' => $br->id, 'ref' => $br->ref, 'title' => $br->title]),
            'linked_trs'        => $task->linkedTrs->map(fn ($tr) => ['id' => $tr->id, 'ref' => $tr->ref, 'title' => $tr->title]),
            'linked_tcs'        => $task->linkedTcs->map(fn ($tc) => ['id' => $tc->id, 'ref' => $tc->ref, 'title' => $tc->title]),
            'logs'              => $task->logs->map(fn ($log) => [
                'id'          => $log->id,
                'hours'       => $log->hours,
                'notes'       => $log->notes,
                'logger_name' => $log->logger->name,
                'created_at'  => $log->created_at->toDateString(),
            ]),
        ];
    }

    public static function sprintItem(Task $task): array
    {
        return [
            'id'                => $task->id,
            'title'             => $task->title,
            'status'            => $task->status->value,
            'status_label'      => $task->status->label(),
            'effort_estimate'   => $task->effort_estimate,
            'effort_unit'       => $task->effort_unit->value,
            'effort_unit_short' => $task->effort_unit->shortLabel(),
            'due_date'          => $task->due_date?->toDateString(),
            'assignee'          => $task->assignee ? ['id' => $task->assignee->id, 'name' => $task->assignee->name] : null,
            'logged_hours'      => $task->totalLoggedHours(),
            'effective_actual'  => $task->effectiveActualHours(),
            'completed_at'      => $task->completed_at?->toDateString(),
        ];
    }

    public static function calendarItem(Task $task): array
    {
        return [
            'id'             => $task->id,
            'title'          => $task->title,
            'status'         => $task->status->value,
            'status_label'   => $task->status->label(),
            'priority'       => $task->priority->value,
            'priority_label' => $task->priority->label(),
            'category'       => $task->category?->value,
            'category_label' => $task->category?->label(),
            'due_date'       => $task->due_date?->toDateString(),
            'assignee'       => $task->assignee ? ['id' => $task->assignee->id, 'name' => $task->assignee->name] : null,
        ];
    }

    public static function ganttItem(Task $task): array
    {
        return [
            'id'             => $task->id,
            'title'          => $task->title,
            'status'         => $task->status->value,
            'status_label'   => $task->status->label(),
            'priority'       => $task->priority->value,
            'priority_label' => $task->priority->label(),
            'start_date'     => $task->start_date?->toDateString(),
            'end_date'       => $task->end_date?->toDateString(),
            'due_date'       => $task->due_date?->toDateString(),
            'assignee'       => $task->assignee ? ['id' => $task->assignee->id, 'name' => $task->assignee->name] : null,
            'sprint'         => $task->sprint ? ['id' => $task->sprint->id, 'name' => $task->sprint->name] : null,
        ];
    }
}
