<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMapCheckpointRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by the policy in the controller
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
            'trip_id' => ['sometimes', 'exists:trips,id'],
            'place_id' => ['nullable', 'exists:places,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'lat' => ['sometimes', 'numeric', 'between:-90,90'],
            'lng' => ['sometimes', 'numeric', 'between:-180,180'],
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
            'trip_id.exists' => 'The selected trip does not exist.',
            'place_id.exists' => 'The selected place does not exist.',
            'title.max' => 'The checkpoint title must not exceed 255 characters.',
            'lat.between' => 'Latitude must be between -90 and 90.',
            'lng.between' => 'Longitude must be between -180 and 180.',
            'checked_in_at.date' => 'The check-in time must be a valid date.',
        ];
    }
}
