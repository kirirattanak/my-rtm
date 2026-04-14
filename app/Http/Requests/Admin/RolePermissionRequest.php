<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RolePermissionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'permissions'   => ['present', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
