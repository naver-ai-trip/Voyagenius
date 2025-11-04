<?php

namespace App\Http\Requests;

use App\Models\MapCheckpoint;
use App\Models\Trip;
use App\Models\TripDiary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
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
            'entity_type' => ['required', 'string', Rule::in(['trip', 'map_checkpoint', 'trip_diary'])],
            'entity_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $entityType = $this->input('entity_type');
                    
                    if (!$entityType) {
                        return; // Let entity_type required validation handle this
                    }

                    // Convert entity_type string to model class
                    $modelClass = match ($entityType) {
                        'trip' => Trip::class,
                        'map_checkpoint' => MapCheckpoint::class,
                        'trip_diary' => TripDiary::class,
                        default => null,
                    };

                    if (!$modelClass) {
                        $fail('Invalid entity type.');
                        return;
                    }

                    // Check if entity exists
                    if (!$modelClass::find($value)) {
                        $fail('The selected entity does not exist.');
                    }
                },
            ],
            'content' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert entity_type to model class for storage
        if ($this->has('entity_type')) {
            $this->merge([
                'entity_class' => match ($this->input('entity_type')) {
                    'trip' => Trip::class,
                    'map_checkpoint' => MapCheckpoint::class,
                    'trip_diary' => TripDiary::class,
                    default => null,
                },
            ]);
        }
    }
}
