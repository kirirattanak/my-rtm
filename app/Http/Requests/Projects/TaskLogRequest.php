<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class TaskLogRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'hours' => ['required', 'numeric', 'min:0.1', 'max:24'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
