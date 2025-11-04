<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Naver\ClovaOcrService;
use App\Services\Naver\ClovaSpeechService;
use App\Services\Naver\PapagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TranslationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Storage::fake('public');
    }

    /** @test */
    public function it_can_translate_text()
    {
        $this->mock(PapagoService::class, function ($mock) {
            $mock->shouldReceive('translate')
                ->once()
                ->with('안녕하세요', 'en', 'ko')
                ->andReturn([
                    'translatedText' => 'Hello',
                    'sourceLang' => 'ko',
                    'targetLang' => 'en',
                ]);
        });

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations/text', [
                'text' => '안녕하세요',
                'source_language' => 'ko',
                'target_language' => 'en',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'source_type',
                    'source_text',
                    'source_language',
                    'translated_text',
                    'target_language',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonFragment([
                'source_type' => 'text',
                'source_text' => '안녕하세요',
                'source_language' => 'ko',
                'translated_text' => 'Hello',
                'target_language' => 'en',
            ]);

        $this->assertDatabaseHas('translations', [
            'user_id' => $this->user->id,
            'source_type' => 'text',
            'source_text' => '안녕하세요',
            'translated_text' => 'Hello',
        ]);
    }

    /** @test */
    public function it_can_translate_text_with_auto_detect()
    {
        $this->mock(PapagoService::class, function ($mock) {
            $mock->shouldReceive('translate')
                ->once()
                ->with('Hello', 'ko', 'auto')
                ->andReturn([
                    'translatedText' => '안녕하세요',
                    'sourceLang' => 'auto',
                    'targetLang' => 'ko',
                ]);
        });

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations/text', [
                'text' => 'Hello',
                'source_language' => 'auto',
                'target_language' => 'ko',
            ]);

        $response->assertCreated()
            ->assertJsonFragment([
                'source_language' => 'auto',
                'translated_text' => '안녕하세요',
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_text_translation()
    {
        $response = $this->postJson('/api/translations/text', [
            'text' => 'Hello',
            'source_language' => 'en',
            'target_language' => 'ko',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_validates_required_fields_for_text_translation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations/text', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['text', 'source_language', 'target_language']);
    }

    /** @test */
    public function it_validates_text_max_length()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations/text', [
                'text' => str_repeat('a', 5001),
                'source_language' => 'en',
                'target_language' => 'ko',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);
    }

    /** @test */
    public function it_can_extract_text_from_image_via_ocr()
    {
        $image = UploadedFile::fake()->image('receipt.jpg');

        $this->mock(ClovaOcrService::class, function ($mock) {
            $mock->shouldReceive('extractText')
                ->once()
                ->andReturn([
                    'text' => 'Total: $50.00',
                    'confidence' => 0.95,
                ]);
        });

        $this->mock(PapagoService::class, function ($mock) {
            $mock->shouldReceive('translate')
                ->once()
                ->with('Total: $50.00', 'ko', 'en')
                ->andReturn([
                    'translatedText' => '합계: $50.00',
                    'sourceLang' => 'en',
                    'targetLang' => 'ko',
                ]);
        });

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations/ocr', [
                'image' => $image,
                'source_language' => 'en',
                'target_language' => 'ko',
            ]);

        $response->assertCreated()
            ->assertJsonFragment([
                'source_type' => 'image',
                'source_text' => 'Total: $50.00',
                'source_language' => 'en',
                'translated_text' => '합계: $50.00',
                'target_language' => 'ko',
            ])
            ->assertJsonPath('data.file_path', fn($path) => str_contains($path, 'translations/'));

        $this->assertDatabaseHas('translations', [
            'user_id' => $this->user->id,
            'source_type' => 'image',
            'source_text' => 'Total: $50.00',
        ]);

        Storage::disk('public')->assertExists(
            $response->json('data.file_path')
        );
    }

    /** @test */
    public function it_requires_authentication_for_ocr()
    {
        $image = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/translations/ocr', [
            'image' => $image,
            'source_language' => 'en',
            'target_language' => 'ko',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_validates_required_fields_for_ocr()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations/ocr', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image', 'source_language', 'target_language']);
    }

    /** @test */
    public function it_validates_image_file_is_valid_image()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations/ocr', [
                'image' => $file,
                'source_language' => 'en',
                'target_language' => 'ko',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function it_validates_image_file_size_limit()
    {
        $image = UploadedFile::fake()->image('large.jpg')->size(11000); // 11MB

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations/ocr', [
                'image' => $image,
                'source_language' => 'en',
                'target_language' => 'ko',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function it_can_transcribe_speech_to_text_and_translate()
    {
        $audio = UploadedFile::fake()->create('speech.mp3', 500, 'audio/mpeg');

        $this->mock(ClovaSpeechService::class, function ($mock) {
            $mock->shouldReceive('speechToText')
                ->once()
                ->andReturn([
                    'text' => '오늘 날씨가 좋습니다',
                    'confidence' => 0.90,
                ]);
        });

        $this->mock(PapagoService::class, function ($mock) {
            $mock->shouldReceive('translate')
                ->once()
                ->with('오늘 날씨가 좋습니다', 'en', 'ko')
                ->andReturn([
                    'translatedText' => 'The weather is nice today',
                    'sourceLang' => 'ko',
                    'targetLang' => 'en',
                ]);
        });

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations/speech', [
                'audio' => $audio,
                'source_language' => 'ko',
                'target_language' => 'en',
            ]);

        $response->assertCreated()
            ->assertJsonFragment([
                'source_type' => 'speech',
                'source_text' => '오늘 날씨가 좋습니다',
                'source_language' => 'ko',
                'translated_text' => 'The weather is nice today',
                'target_language' => 'en',
            ])
            ->assertJsonPath('data.file_path', fn($path) => str_contains($path, 'translations/'));

        $this->assertDatabaseHas('translations', [
            'user_id' => $this->user->id,
            'source_type' => 'speech',
            'source_text' => '오늘 날씨가 좋습니다',
        ]);

        Storage::disk('public')->assertExists(
            $response->json('data.file_path')
        );
    }

    /** @test */
    public function it_requires_authentication_for_speech_translation()
    {
        $audio = UploadedFile::fake()->create('speech.mp3', 500, 'audio/mpeg');

        $response = $this->postJson('/api/translations/speech', [
            'audio' => $audio,
            'source_language' => 'ko',
            'target_language' => 'en',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_validates_required_fields_for_speech_translation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations/speech', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['audio', 'source_language', 'target_language']);
    }

    /** @test */
    public function it_validates_audio_file_is_valid_audio()
    {
        $file = UploadedFile::fake()->image('image.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations/speech', [
                'audio' => $file,
                'source_language' => 'ko',
                'target_language' => 'en',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['audio']);
    }

    /** @test */
    public function it_validates_audio_file_size_limit()
    {
        $audio = UploadedFile::fake()->create('large.mp3', 21000, 'audio/mpeg'); // 21MB

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/translations/speech', [
                'audio' => $audio,
                'source_language' => 'ko',
                'target_language' => 'en',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['audio']);
    }

    /** @test */
    public function it_can_list_user_translations()
    {
        // Create some translations
        \App\Models\Translation::factory()->count(20)->create(['user_id' => $this->user->id]);
        \App\Models\Translation::factory()->count(5)->create(); // Other users

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations');

        $response->assertOk()
            ->assertJsonCount(15, 'data') // Default pagination
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'source_type',
                        'source_text',
                        'source_language',
                        'translated_text',
                        'target_language',
                        'file_path',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /** @test */
    public function it_requires_authentication_to_list_translations()
    {
        $response = $this->getJson('/api/translations');

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_can_filter_translations_by_source_type()
    {
        \App\Models\Translation::factory()->create([
            'user_id' => $this->user->id,
            'source_type' => 'text',
        ]);
        \App\Models\Translation::factory()->create([
            'user_id' => $this->user->id,
            'source_type' => 'image',
        ]);
        \App\Models\Translation::factory()->create([
            'user_id' => $this->user->id,
            'source_type' => 'speech',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations?source_type=text');

        $response->assertOk()
            ->assertJsonCount(1, 'data');

        $sourceTypes = collect($response->json('data'))->pluck('source_type')->unique();
        $this->assertEquals(['text'], $sourceTypes->toArray());
    }

    /** @test */
    public function it_can_filter_translations_by_language_pair()
    {
        \App\Models\Translation::factory()->create([
            'user_id' => $this->user->id,
            'source_language' => 'en',
            'target_language' => 'ko',
        ]);
        \App\Models\Translation::factory()->create([
            'user_id' => $this->user->id,
            'source_language' => 'ko',
            'target_language' => 'en',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations?source_language=en&target_language=ko');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'source_language' => 'en',
                'target_language' => 'ko',
            ]);
    }

    /** @test */
    public function it_can_view_single_translation()
    {
        $translation = \App\Models\Translation::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/translations/{$translation->id}");

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $translation->id,
                'source_text' => $translation->source_text,
                'translated_text' => $translation->translated_text,
            ]);
    }

    /** @test */
    public function it_prevents_viewing_other_users_translation()
    {
        $otherUser = User::factory()->create();
        $translation = \App\Models\Translation::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/translations/{$translation->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function it_can_delete_translation()
    {
        $translation = \App\Models\Translation::factory()->create([
            'user_id' => $this->user->id,
            'file_path' => 'translations/test.jpg',
        ]);

        Storage::disk('public')->put($translation->file_path, 'test content');

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/translations/{$translation->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('translations', [
            'id' => $translation->id,
        ]);

        Storage::disk('public')->assertMissing($translation->file_path);
    }

    /** @test */
    public function it_prevents_deleting_other_users_translation()
    {
        $otherUser = User::factory()->create();
        $translation = \App\Models\Translation::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/translations/{$translation->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
        ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_translation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations/999');

        $response->assertNotFound();
    }

    /** @test */
    public function it_lists_translations_ordered_by_created_at_descending()
    {
        $old = \App\Models\Translation::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2),
        ]);
        $recent = \App\Models\Translation::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/translations');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertEquals([$recent->id, $old->id], $ids);
    }

    /** @test */
    public function it_supports_common_audio_formats()
    {
        $formats = [
            ['ext' => 'mp3', 'mime' => 'audio/mpeg'],
            ['ext' => 'wav', 'mime' => 'audio/wav'],
            ['ext' => 'm4a', 'mime' => 'audio/mp4'],
        ];

        $this->mock(ClovaSpeechService::class, function ($mock) {
            $mock->shouldReceive('speechToText')
                ->times(3)
                ->andReturn(['text' => 'Test', 'confidence' => 0.90]);
        });

        $this->mock(PapagoService::class, function ($mock) {
            $mock->shouldReceive('translate')
                ->times(3)
                ->andReturn([
                    'translatedText' => 'Test',
                    'sourceLang' => 'en',
                    'targetLang' => 'ko',
                ]);
        });

        foreach ($formats as $format) {
            $audio = UploadedFile::fake()->create("speech.{$format['ext']}", 500, $format['mime']);

            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/translations/speech', [
                    'audio' => $audio,
                    'source_language' => 'en',
                    'target_language' => 'ko',
                ]);

            $response->assertCreated();
        }
    }
}
