<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestSuiteRequest extends FormRequest
{
    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'br_ids'      => ['required', 'array', 'min:1'],
            'br_ids.*'    => ['integer', Rule::exists('business_requirements', 'id')->where('project_id', $project->id)],
        ];
    }
}
