<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class InvitationStoreRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'email' => ['required', 'email', 'unique:users,email', 'unique:invitations,email'],
            'role'  => ['required', 'string', 'exists:roles,slug'],
        ];

        if ($this->user()->isAdmin()) {
            $rules['organization_id'] = ['nullable', 'exists:organizations,id'];
        }

        return $rules;
    }
}
