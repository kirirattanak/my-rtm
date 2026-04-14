<?php

namespace App\Http\Requests\Projects;

use App\Enums\BrPriority;
use App\Enums\RequirementStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BusinessRequirementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'             => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'priority'          => ['required', Rule::in(BrPriority::values())],
            'status'            => ['required', Rule::in(RequirementStatus::values())],
            'category'          => ['nullable', 'string', 'max:100'],
            'tags'              => ['nullable', 'array'],
            'tags.*'            => ['string', 'max:50'],
            'optimistic_hours'  => ['nullable', 'numeric', 'min:0'],
            'most_likely_hours' => ['nullable', 'numeric', 'min:0'],
            'pessimistic_hours' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $o = $this->input('optimistic_hours');
            $m = $this->input('most_likely_hours');
            $p = $this->input('pessimistic_hours');

            // If any PERT field is provided, all three must be present
            $provided = array_filter([$o, $m, $p], fn ($v) => $v !== null && $v !== '');
            if (count($provided) > 0 && count($provided) < 3) {
                $v->errors()->add('optimistic_hours', 'All three PERT estimate fields must be filled in together.');
                return;
            }

            if (count($provided) === 3) {
                if ((float) $o > (float) $m) {
                    $v->errors()->add('most_likely_hours', 'Most likely hours must be ≥ optimistic hours.');
                }
                if ((float) $m > (float) $p) {
                    $v->errors()->add('pessimistic_hours', 'Pessimistic hours must be ≥ most likely hours.');
                }
            }
        });
    }
}
