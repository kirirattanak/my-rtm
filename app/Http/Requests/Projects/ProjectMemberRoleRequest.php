<?php

namespace App\Http\Requests\Projects;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectMemberRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(UserRole::values())],
        ];
    }
}
