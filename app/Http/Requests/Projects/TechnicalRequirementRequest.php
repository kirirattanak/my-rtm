<?php

namespace App\Http\Requests\Projects;

use App\Enums\RequirementStatus;
use App\Enums\TrType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TechnicalRequirementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type'        => ['required', Rule::in(TrType::values())],
            'status'      => ['required', Rule::in(RequirementStatus::values())],
        ];
    }
}
