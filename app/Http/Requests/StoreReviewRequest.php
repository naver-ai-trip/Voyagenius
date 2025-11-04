<?php

namespace App\Http\Requests;

use App\Models\MapCheckpoint;
use App\Models\Place;
use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Let validation handle all field validation first
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
            'reviewable_type' => ['required', 'string', Rule::in(['place', 'map_checkpoint'])],
            'reviewable_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $type = $this->input('reviewable_type');
                    
                    // Validate that the reviewable exists based on type
                    if ($type === 'place' && !Place::find($value)) {
                        $fail('The selected place does not exist.');
                    } elseif ($type === 'map_checkpoint' && !MapCheckpoint::find($value)) {
                        $fail('The selected checkpoint does not exist.');
                    }
                    
                    // Check for duplicate review (user already reviewed this entity)
                    if ($type && $value) {
                        $modelClass = $type === 'place' ? Place::class : MapCheckpoint::class;
                        
                        $exists = Review::where('user_id', $this->user()->id)
                            ->where('reviewable_type', $modelClass)
                            ->where('reviewable_id', $value)
                            ->exists();
                        
                        if ($exists) {
                            $fail('You have already reviewed this ' . str_replace('_', ' ', $type) . '.');
                        }
                    }
                },
            ],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reviewable_type.required' => 'The reviewable type is required.',
            'reviewable_type.in' => 'The reviewable type must be either place or map_checkpoint.',
            'reviewable_id.required' => 'The reviewable ID is required.',
            'reviewable_id.integer' => 'The reviewable ID must be an integer.',
            'rating.required' => 'A rating is required.',
            'rating.between' => 'The rating must be between 1 and 5.',
            'comment.max' => 'The comment must not exceed 1000 characters.',
        ];
    }
}
