<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgentActionRequest extends FormRequest
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
            'action_type' => ['required', 'string', 'in:create_trip,search_places,add_to_itinerary,translate_content,get_recommendations,update_preferences'],
            'input_data' => ['nullable', 'array'],
            'entity_type' => ['nullable', 'string'],
            'entity_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'action_type.required' => 'The action type is required.',
            'action_type.in' => 'The action type must be one of: create_trip, search_places, add_to_itinerary, translate_content, get_recommendations, update_preferences.',
        ];
    }
}
