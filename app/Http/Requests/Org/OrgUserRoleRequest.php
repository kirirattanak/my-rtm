<?php

namespace App\Http\Requests\Org;

use Illuminate\Foundation\Http\FormRequest;

class OrgUserRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'role_id' => ['required', 'exists:roles,id'],
        ];
    }
}
