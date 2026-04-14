<?php

namespace App\Http\Requests\Projects;

use App\Models\User;
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
                'integer',
                Rule::exists('users', 'id')->where('is_active', true),
                Rule::unique('project_members')->where('project_id', $project->id),
                function (string $attribute, mixed $value, \Closure $fail) use ($project) {
                    if (!$project->organization_id) {
                        return;
                    }
                    $orgId = User::where('id', $value)->value('organization_id');
                    if ($orgId !== $project->organization_id) {
                        $fail('The selected user does not belong to this project\'s organization.');
                    }
                },
            ],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ];
    }
}
