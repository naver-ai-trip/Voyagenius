<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TranslateOcrRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authenticated via sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Accept either file upload or URL string
            'image' => [
                'required',
                function ($attribute, $value, $fail) {
                    // Check if it's a file upload
                    if ($this->hasFile('image')) {
                        $file = $this->file('image');
                        if (!$file->isValid()) {
                            $fail('The image file is invalid.');
                        }
                        return;
                    }
                    // Otherwise, it must be a URL
                    if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_URL)) {
                        $fail('The image must be either a valid file upload or a valid URL.');
                    }
                },
            ],
            'source_language' => ['nullable', 'string'],
            'target_language' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'The image field is required (either file upload or URL).',
        ];
    }
}
