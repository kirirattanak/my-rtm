<?php

namespace App\Http\Requests\Org;

use Illuminate\Foundation\Http\FormRequest;

class OrgRolePermissionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'permissions'   => ['present', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ];
    }
}
