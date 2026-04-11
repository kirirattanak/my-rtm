<?php

namespace App\Http\Requests\Projects;

use App\Enums\BrPriority;
use App\Enums\EffortUnit;
use App\Enums\TaskCategory;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'sprint_id'       => ['nullable', 'exists:sprints,id'],
            'effort_estimate' => ['nullable', 'numeric', 'min:0'],
            'effort_unit'     => ['required', Rule::in(EffortUnit::values())],
            'status'          => ['required', Rule::in(TaskStatus::values())],
            'priority'        => ['required', Rule::in(BrPriority::values())],
            'category'        => ['nullable', Rule::in(TaskCategory::values())],
            'due_date'        => ['nullable', 'date'],
            'start_date'      => ['nullable', 'date'],
            'end_date'        => ['nullable', 'date', 'after_or_equal:start_date'],
            'assignee_id'     => ['nullable', 'exists:users,id'],
            'linked_br_ids'   => ['nullable', 'array'],
            'linked_br_ids.*' => ['integer', 'exists:business_requirements,id'],
            'linked_tr_ids'   => ['nullable', 'array'],
            'linked_tr_ids.*' => ['integer', 'exists:technical_requirements,id'],
            'linked_tc_ids'   => ['nullable', 'array'],
            'linked_tc_ids.*' => ['integer', 'exists:test_cases,id'],
        ];
    }
}
