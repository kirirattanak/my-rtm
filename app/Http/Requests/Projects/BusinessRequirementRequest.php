<?php

namespace App\Http\Requests\Projects;

use App\Enums\BrPriority;
use App\Enums\RequirementStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BusinessRequirementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority'    => ['required', Rule::in(BrPriority::values())],
            'status'      => ['required', Rule::in(RequirementStatus::values())],
            'category'    => ['nullable', 'string', 'max:100'],
            'tags'        => ['nullable', 'array'],
            'tags.*'      => ['string', 'max:50'],
        ];
    }
}
