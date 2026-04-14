<?php

namespace App\Http\Requests\Org;

use Illuminate\Foundation\Http\FormRequest;

class OrgInviteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email'   => ['required', 'email'],
            'role_id' => ['required', 'exists:roles,id'],
        ];
    }
}
