<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate flight offer search requests.
 */
class SearchFlightOffersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Sanctum middleware gates access
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'departure_id' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'arrival_id' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'outbound_date' => ['required', 'date', 'after_or_equal:today', 'date_format:Y-m-d'],
            'return_date' => ['nullable', 'date', 'after:outbound_date', 'date_format:Y-m-d'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert to uppercase for IATA codes
        if ($this->has('departure_id')) {
            $this->merge([
                'departure_id' => strtoupper($this->input('departure_id')),
            ]);
        }

        if ($this->has('arrival_id')) {
            $this->merge([
                'arrival_id' => strtoupper($this->input('arrival_id')),
            ]);
        }
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'departure_id.required' => 'Departure airport IATA code is required.',
            'departure_id.size' => 'Departure airport code must be exactly 3 characters.',
            'departure_id.regex' => 'Departure airport code must be a valid 3-letter IATA code (e.g., LAX, JFK).',
            'arrival_id.required' => 'Arrival airport IATA code is required.',
            'arrival_id.size' => 'Arrival airport code must be exactly 3 characters.',
            'arrival_id.regex' => 'Arrival airport code must be a valid 3-letter IATA code (e.g., AUS, SFO).',
            'outbound_date.required' => 'Outbound date is required.',
            'outbound_date.after_or_equal' => 'Outbound date must be today or in the future.',
            'outbound_date.date_format' => 'Outbound date must be in YYYY-MM-DD format.',
            'return_date.after' => 'Return date must be after the outbound date.',
            'return_date.date_format' => 'Return date must be in YYYY-MM-DD format.',
        ];
    }
}
