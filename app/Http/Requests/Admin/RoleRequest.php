<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $role
                    ? Rule::unique('roles', 'name')->ignore($role->id)
                    : Rule::unique('roles', 'name'),
            ],
        ];
    }
}
