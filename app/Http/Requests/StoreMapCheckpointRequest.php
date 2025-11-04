<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMapCheckpointRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if the user owns the trip they're trying to create a checkpoint for
        // This runs AFTER validation passes
        $tripId = $this->input('trip_id');
        
        if (!$tripId) {
            return true; // Let validation handle missing trip_id
        }
        
        $trip = \App\Models\Trip::find($tripId);
        
        if (!$trip) {
            return true; // Let validation handle non-existent trip_id
        }
        
        return $this->user()->id === $trip->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'trip_id' => ['required', 'exists:trips,id'],
            'place_id' => ['nullable', 'exists:places,id'],
            'title' => ['required', 'string', 'max:255'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'note' => ['nullable', 'string'],
            'checked_in_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trip_id.required' => 'A trip ID is required.',
            'trip_id.exists' => 'The selected trip does not exist.',
            'place_id.exists' => 'The selected place does not exist.',
            'title.required' => 'A checkpoint title is required.',
            'title.max' => 'The checkpoint title must not exceed 255 characters.',
            'lat.required' => 'Latitude is required.',
            'lat.between' => 'Latitude must be between -90 and 90.',
            'lng.required' => 'Longitude is required.',
            'lng.between' => 'Longitude must be between -180 and 180.',
            'checked_in_at.date' => 'The check-in time must be a valid date.',
        ];
    }
}
