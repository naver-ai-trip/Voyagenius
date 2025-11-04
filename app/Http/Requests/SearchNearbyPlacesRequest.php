<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchNearbyPlacesRequest extends FormRequest
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
            'radius' => ['nullable', 'integer', 'min:1', 'max:10000'], // Max 10km
            'query' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom attribute names for error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'latitude' => 'latitude coordinate',
            'longitude' => 'longitude coordinate',
            'radius' => 'search radius',
        ];
    }

    /**
     * Get the radius value or return default.
     */
    public function getRadius(): int
    {
        return $this->input('radius', 1000); // Default 1km
    }
}
