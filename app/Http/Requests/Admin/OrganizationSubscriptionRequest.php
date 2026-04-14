<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationSubscriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tier_option_id' => ['required', 'exists:subscription_tier_options,id'],
            'status'         => ['required', Rule::in(['trial', 'active', 'expired', 'cancelled'])],
        ];
    }
}
