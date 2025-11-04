<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DetachTagRequest extends FormRequest
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
            'tag_id' => ['required', 'integer', 'exists:tags,id'],
            'taggable_type' => ['required', 'string', Rule::in(['trip', 'place', 'checkpoint'])],
            'taggable_id' => ['required', 'integer', $this->taggableExistsRule()],
        ];
    }

    /**
     * Get the validation rule for taggable_id based on taggable_type.
     */
    private function taggableExistsRule()
    {
        $type = $this->input('taggable_type');

        return match ($type) {
            'trip' => 'exists:trips,id',
            'place' => 'exists:places,id',
            'checkpoint' => 'exists:map_checkpoints,id',
            default => '', // Will fail validation if type is invalid
        };
    }
}
