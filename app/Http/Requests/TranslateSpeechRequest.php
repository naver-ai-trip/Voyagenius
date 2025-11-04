<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TranslateSpeechRequest extends FormRequest
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
            'audio' => ['required', 'file', 'mimes:audio/mpeg,audio/wav,audio/mp4,mpga,mp3,wav,m4a', 'max:20480'], // 20MB in KB
            'source_language' => ['required', 'string'],
            'target_language' => ['required', 'string'],
        ];
    }
}
