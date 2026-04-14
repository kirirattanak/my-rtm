<?php

namespace App\Http\Requests\Org;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrgRoleRequest extends FormRequest
{
    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                $role
                    ? Rule::unique('roles', 'name')->ignore($role->id)
                    : Rule::unique('roles', 'name'),
            ],
        ];
    }
}
