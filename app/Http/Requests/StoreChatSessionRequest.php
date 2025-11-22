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
        // If trip_id is provided, verify user owns the trip
        if ($this->has('trip_id') && $this->input('trip_id')) {
            $trip = \App\Models\Trip::find($this->input('trip_id'));
            
            if (!$trip) {
                return false;
            }
            
            // User must own the trip or be a participant
            return $trip->user_id === $this->user()->id
                || $trip->participants()->where('user_id', $this->user()->id)->exists();
        }
        
        return true;
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

    /**
     * Get custom authorization failure message.
     */
    protected function failedAuthorization()
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'You do not have permission to create a chat session for this trip. You must be the trip owner or a participant.'
        );
    }
}
