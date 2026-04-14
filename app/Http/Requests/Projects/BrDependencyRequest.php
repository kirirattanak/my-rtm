<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrDependencyRequest extends FormRequest
{
    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'blocking_br_id' => [
                'required',
                'integer',
                Rule::exists('business_requirements', 'id')->where('project_id', $project->id),
            ],
        ];
    }
}
