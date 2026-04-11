<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkTrRequest extends FormRequest
{
    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'technical_requirement_id' => [
                'required',
                'integer',
                Rule::exists('technical_requirements', 'id')->where('project_id', $project->id),
            ],
        ];
    }
}
