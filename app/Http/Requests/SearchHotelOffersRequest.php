<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchHotelOffersRequest extends FormRequest
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
            'hotel_ids' => ['required', 'array', 'min:1', 'max:100'],
            'hotel_ids.*' => ['required', 'string'],
            'check_in_date' => ['required', 'date', 'after_or_equal:today', 'date_format:Y-m-d'],
            'check_out_date' => ['required', 'date', 'after:check_in_date', 'date_format:Y-m-d'],
            'adults' => ['sometimes', 'integer', 'min:1', 'max:9'],
            'room_quantity' => ['sometimes', 'integer', 'min:1', 'max:9'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'price_range' => ['sometimes', 'string', 'regex:/^\d+-\d+$/'],
            'payment_policy' => ['sometimes', 'string', Rule::in(['NONE', 'GUARANTEE', 'DEPOSIT'])],
            'board_type' => ['sometimes', 'string', Rule::in(['ROOM_ONLY', 'BREAKFAST', 'HALF_BOARD', 'FULL_BOARD', 'ALL_INCLUSIVE'])],
            'include_closed' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'hotel_ids.required' => 'At least one hotel ID is required',
            'hotel_ids.max' => 'Maximum 100 hotel IDs allowed',
            'check_in_date.required' => 'Check-in date is required',
            'check_in_date.after_or_equal' => 'Check-in date must be today or in the future',
            'check_in_date.date_format' => 'Check-in date must be in YYYY-MM-DD format',
            'check_out_date.required' => 'Check-out date is required',
            'check_out_date.after' => 'Check-out date must be after check-in date',
            'check_out_date.date_format' => 'Check-out date must be in YYYY-MM-DD format',
        ];
    }
}

