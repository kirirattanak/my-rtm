<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class OrganizationUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'owner_id'  => ['nullable', 'exists:users,id'],
            'is_active' => ['boolean'],
        ];
    }
}
