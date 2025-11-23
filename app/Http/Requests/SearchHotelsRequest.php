<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchHotelsRequest extends FormRequest
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
            'search_type' => ['required', 'string', Rule::in(['city', 'geocode', 'hotel_ids'])],
            
            // For city search
            'city_code' => ['required_if:search_type,city', 'string', 'size:3'],
            
            // For geocode search
            'latitude' => ['required_if:search_type,geocode', 'numeric', 'between:-90,90'],
            'longitude' => ['required_if:search_type,geocode', 'numeric', 'between:-180,180'],
            'radius' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'radius_unit' => ['sometimes', 'string', Rule::in(['KM', 'MILE'])],
            
            // For hotel IDs search
            'hotel_ids' => ['required_if:search_type,hotel_ids', 'array', 'min:1', 'max:100'],
            'hotel_ids.*' => ['string'],
            
            // Common optional parameters
            'hotel_source' => ['sometimes', 'string', Rule::in(['ALL', 'GDS'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'search_type.required' => 'Search type is required (city, geocode, or hotel_ids)',
            'search_type.in' => 'Search type must be one of: city, geocode, hotel_ids',
            'city_code.required_if' => 'City code is required when search type is city',
            'city_code.size' => 'City code must be exactly 3 characters (IATA code)',
            'latitude.required_if' => 'Latitude is required when search type is geocode',
            'longitude.required_if' => 'Longitude is required when search type is geocode',
            'hotel_ids.required_if' => 'Hotel IDs are required when search type is hotel_ids',
            'hotel_ids.max' => 'Maximum 100 hotel IDs allowed',
        ];
    }
}

