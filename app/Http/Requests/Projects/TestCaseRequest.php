<?php

namespace App\Http\Requests\Projects;

use App\Enums\BrPriority;
use App\Enums\RequirementStatus;
use App\Enums\TestCaseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestCaseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'steps'           => ['nullable', 'array'],
            'steps.*'         => ['string', 'max:500'],
            'expected_result' => ['nullable', 'string'],
            'type'            => ['required', Rule::in(TestCaseType::values())],
            'priority'        => ['required', Rule::in(BrPriority::values())],
            'status'          => ['required', Rule::in(RequirementStatus::values())],
            'assignee_id'     => ['nullable', 'exists:users,id'],
        ];
    }
}
