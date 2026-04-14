<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UserRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ];
    }
}
