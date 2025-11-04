<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripDiaryRequest extends FormRequest
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
            'trip_id' => ['required', 'integer', 'exists:trips,id'],
            'entry_date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    // Check for duplicate entry on same trip and date
                    $exists = \App\Models\TripDiary::where('trip_id', $this->input('trip_id'))
                        ->where('user_id', auth()->id())
                        ->whereDate('entry_date', $value)
                        ->exists();

                    if ($exists) {
                        $fail('You already have a diary entry for this trip on this date.');
                    }
                },
            ],
            'text' => ['nullable', 'string'],
            'mood' => ['nullable', 'string'],
        ];
    }
}
