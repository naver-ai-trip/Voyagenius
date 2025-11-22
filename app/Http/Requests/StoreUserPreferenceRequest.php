<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserPreferenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller/policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'preference_type' => [
                'required',
                'string',
                'in:travel_style,budget_range,dietary_restrictions,accommodation_type,activity_level,language_preference',
                Rule::unique('user_preferences')->where(function ($query) {
                    return $query->where('user_id', $this->user()->id);
                }),
            ],
            'value' => ['required', 'array'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'preference_type.required' => 'The preference type is required.',
            'preference_type.in' => 'The preference type must be one of: travel_style, budget_range, dietary_restrictions, accommodation_type, activity_level, language_preference.',
            'preference_type.unique' => 'You already have a preference of this type. Please update the existing one instead.',
            'value.required' => 'The preference value is required.',
            'value.array' => 'The preference value must be a JSON object.',
            'priority.min' => 'Priority must be at least 1.',
            'priority.max' => 'Priority cannot exceed 10.',
        ];
    }
}
