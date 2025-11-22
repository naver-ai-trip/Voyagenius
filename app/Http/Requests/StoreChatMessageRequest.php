<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller/policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string'],
            'from_role' => ['nullable', 'string', 'in:user,ai'],
            'metadata' => ['nullable', 'array'],
            'entity_type' => ['nullable', 'string'],
            'entity_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'message.required' => 'The message content is required.',
            'from_role.in' => 'The from_role must be either user or ai.',
        ];
    }
}
