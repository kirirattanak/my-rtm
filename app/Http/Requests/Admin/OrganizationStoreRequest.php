<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'slug'           => ['required', 'string', 'max:255', 'unique:organizations,slug', 'regex:/^[a-z0-9\-]+$/'],
            'tier_option_id' => ['required', 'exists:subscription_tier_options,id'],
            'is_active'      => ['boolean'],
            'owner_id'       => ['nullable', 'exists:users,id'],
            'owner_name'     => ['required_without:owner_id', 'string', 'max:255'],
            'owner_email'    => ['required_without:owner_id', 'email', 'unique:users,email'],
        ];
    }
}
