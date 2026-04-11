<?php

namespace App\Http\Requests\Projects;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectMemberRequest extends FormRequest
{
    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('project_members')->where('project_id', $project->id),
            ],
            'role' => ['required', Rule::in(UserRole::values())],
        ];
    }
}
