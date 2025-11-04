<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Translation>
 */
class TranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sourceType = fake()->randomElement(['text', 'image', 'speech']);
        $languages = ['ko', 'en', 'ja', 'zh-CN', 'es', 'fr', 'de'];
        $sourceLanguage = fake()->randomElement($languages);
        $targetLanguage = fake()->randomElement(array_diff($languages, [$sourceLanguage]));

        return [
            'user_id' => \App\Models\User::factory(),
            'source_type' => $sourceType,
            'source_text' => fake()->sentence(),
            'source_language' => $sourceLanguage,
            'translated_text' => fake()->sentence(),
            'target_language' => $targetLanguage,
            'file_path' => $sourceType !== 'text' 
                ? 'translations/' . fake()->uuid() . '.' . ($sourceType === 'image' ? 'jpg' : 'mp3')
                : null,
        ];
    }
}
