<?php

namespace App\Http\Requests\Projects;

use App\Enums\RequirementStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequirementStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(RequirementStatus::values())],
        ];
    }
}
