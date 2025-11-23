<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchHotelsWithOffersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authentication handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'check_in_date' => ['required', 'date', 'after_or_equal:today', 'date_format:Y-m-d'],
            'check_out_date' => ['required', 'date', 'after:check_in_date', 'date_format:Y-m-d'],
            'radius' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'radius_unit' => ['sometimes', 'string', Rule::in(['KM', 'MILE'])],
            'adults' => ['sometimes', 'integer', 'min:1', 'max:9'],
            'room_quantity' => ['sometimes', 'integer', 'min:1', 'max:9'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Latitude is required',
            'longitude.required' => 'Longitude is required',
            'check_in_date.required' => 'Check-in date is required',
            'check_in_date.after_or_equal' => 'Check-in date must be today or in the future',
            'check_in_date.date_format' => 'Check-in date must be in YYYY-MM-DD format',
            'check_out_date.required' => 'Check-out date is required',
            'check_out_date.after' => 'Check-out date must be after check-in date',
            'check_out_date.date_format' => 'Check-out date must be in YYYY-MM-DD format',
        ];
    }
}

