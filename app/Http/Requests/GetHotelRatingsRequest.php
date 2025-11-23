<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetHotelRatingsRequest extends FormRequest
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
            'hotel_ids' => ['required', 'array', 'min:1', 'max:3'],
            'hotel_ids.*' => ['required', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'hotel_ids.required' => 'At least one hotel ID is required',
            'hotel_ids.min' => 'At least one hotel ID is required',
            'hotel_ids.max' => 'Maximum 3 hotel IDs allowed per request',
        ];
    }
}

