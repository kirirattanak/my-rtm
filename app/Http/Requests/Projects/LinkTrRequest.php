<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class LinkTrRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'technical_requirement_id' => ['required', 'integer', 'exists:technical_requirements,id'],
        ];
    }
}
