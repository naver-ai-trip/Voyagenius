<?php

namespace App\Http\Requests;

use App\Models\Favorite;
use App\Models\MapCheckpoint;
use App\Models\Place;
use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFavoriteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by authentication middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'favoritable_type' => ['required', Rule::in(['place', 'trip', 'map_checkpoint'])],
            'favoritable_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $validated = $this->safe()->only(['favoritable_type']);
                    $favoritableType = $validated['favoritable_type'] ?? null;

                    if (!$favoritableType) {
                        return; // Let the favoritable_type validation handle this
                    }

                    // Convert simple type to model class
                    $modelClass = match ($favoritableType) {
                        'place' => Place::class,
                        'trip' => Trip::class,
                        'map_checkpoint' => MapCheckpoint::class,
                        default => null,
                    };

                    if (!$modelClass) {
                        return;
                    }

                    // Check if the entity exists
                    if (!$modelClass::where('id', $value)->exists()) {
                        $fail('The selected entity does not exist.');
                    }

                    // Check if user already favorited this entity
                    $exists = Favorite::where('user_id', auth()->id())
                        ->where('favoritable_type', $modelClass)
                        ->where('favoritable_id', $value)
                        ->exists();

                    if ($exists) {
                        $fail('You have already favorited this entity.');
                    }
                },
            ],
        ];
    }
}
