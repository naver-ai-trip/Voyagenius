<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatSessionRequest extends FormRequest
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
            'trip_id' => ['nullable', 'exists:trips,id'],
            'session_type' => ['required', 'string', 'in:trip_planning,itinerary_building,place_search,recommendation'],
            'context' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'session_type.in' => 'The session type must be one of: trip_planning, itinerary_building, place_search, recommendation.',
            'trip_id.exists' => 'The selected trip does not exist.',
        ];
    }
}
