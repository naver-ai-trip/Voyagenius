<?php

namespace App\Http\Requests;

use App\Models\TripParticipant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripParticipantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $trip = $this->route('trip');
                    $tripId = $trip instanceof \App\Models\Trip ? $trip->id : $trip;

                    // Check if user is already a participant
                    $exists = TripParticipant::where('trip_id', $tripId)
                        ->where('user_id', $value)
                        ->exists();

                    if ($exists) {
                        $fail('This user is already a participant of this trip.');
                    }
                },
            ],
            'role' => ['required', 'string', Rule::in(['owner', 'editor', 'viewer'])],
        ];
    }
}

