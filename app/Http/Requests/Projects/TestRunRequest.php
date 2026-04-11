<?php

namespace App\Http\Requests\Projects;

use App\Enums\TestRunStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestRunRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(TestRunStatus::values())],
            'notes'  => ['nullable', 'string', 'max:1000'],
        ];
    }
}
