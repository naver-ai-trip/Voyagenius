<?php

namespace Tests\Unit\Services;

use App\Exceptions\NaverApiException;
use App\Services\Naver\PapagoService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test suite for NAVER Cloud Platform Papago Translation API Service
 *
 * NAVER API Integration Notes:
 * - These tests use HTTP fakes to stub Papago API responses
 * - Real API calls are NOT made during testing
 * - Production code WILL make real API calls when enabled
 * - Authentication uses NCP Gateway (X-NCP-APIGW-API-KEY-ID and X-NCP-APIGW-API-KEY headers)
 * - Base URL: https://papago.apigw.ntruss.com
 * - Supports 15 languages: ko, en, ja, zh-CN, zh-TW, vi, id, th, de, ru, es, it, fr, hi, pt
 */
class PapagoServiceTest extends TestCase
{
    private PapagoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.naver.papago', [
            'client_id' => 'test_papago_id',
            'client_secret' => 'test_papago_secret',
            'base_url' => 'https://papago.apigw.ntruss.com',
            'enabled' => true,
        ]);

        $this->service = new PapagoService();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_translate_text_from_korean_to_english(): void
    {
        Http::fake([
            'papago.apigw.ntruss.com/*' => Http::response([
                'message' => [
                    'result' => [
                        'srcLangType' => 'ko',
                        'tarLangType' => 'en',
                        'translatedText' => 'Hello World',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->translate('안녕하세요', 'en', 'ko');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('translatedText', $result);
        $this->assertArrayHasKey('sourceLang', $result);
        $this->assertArrayHasKey('targetLang', $result);
        $this->assertEquals('Hello World', $result['translatedText']);
        $this->assertEquals('ko', $result['sourceLang']);
        $this->assertEquals('en', $result['targetLang']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_auto_detect_source_language(): void
    {
        // First detect language
        Http::fake([
            'papago.apigw.ntruss.com/*' => Http::sequence()
                ->push([
                    'langCode' => 'ko',
                    'confidence' => 0.9,
                ], 200)
                ->push([
                    'message' => [
                        'result' => [
                            'srcLangType' => 'ko',
                            'tarLangType' => 'en',
                            'translatedText' => 'Hello',
                        ],
                    ],
                ], 200),
        ]);

        $result = $this->service->translate('안녕', 'en');

        $this->assertEquals('Hello', $result['translatedText']);
        $this->assertEquals('ko', $result['sourceLang']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_original_text_when_source_equals_target(): void
    {
        $result = $this->service->translate('Hello World', 'en', 'en');

        $this->assertEquals('Hello World', $result['translatedText']);
        $this->assertEquals('en', $result['sourceLang']);
        $this->assertEquals('en', $result['targetLang']);

        // Should not make any HTTP calls
        Http::assertNothingSent();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_detect_language(): void
    {
        Http::fake([
            'papago.apigw.ntruss.com/*' => Http::response([
                'langCode' => 'ko',
                'confidence' => 0.95,
            ], 200),
        ]);

        $result = $this->service->detectLanguage('안녕하세요');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('langCode', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertEquals('ko', $result['langCode']);
        $this->assertEquals(0.95, $result['confidence']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_translate_batch_texts(): void
    {
        Http::fake([
            'papago.apigw.ntruss.com/*' => Http::sequence()
                ->push(['langCode' => 'ko', 'confidence' => 0.9], 200)
                ->push([
                    'message' => [
                        'result' => [
                            'srcLangType' => 'ko',
                            'tarLangType' => 'en',
                            'translatedText' => 'Hello',
                        ],
                    ],
                ], 200)
                ->push(['langCode' => 'ko', 'confidence' => 0.9], 200)
                ->push([
                    'message' => [
                        'result' => [
                            'srcLangType' => 'ko',
                            'tarLangType' => 'en',
                            'translatedText' => 'Thank you',
                        ],
                    ],
                ], 200),
        ]);

        $texts = ['안녕하세요', '감사합니다'];
        $results = $this->service->translateBatch($texts, 'en');

        $this->assertCount(2, $results);
        $this->assertEquals('Hello', $results[0]['translatedText']);
        $this->assertEquals('Thank you', $results[1]['translatedText']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_batch_translation_errors_gracefully(): void
    {
        Http::fake([
            'papago.apigw.ntruss.com/*' => Http::sequence()
                ->push([
                    'langCode' => 'ko',
                    'confidence' => 0.9,
                ], 200)
                ->push([
                    'message' => [
                        'result' => [
                            'srcLangType' => 'ko',
                            'tarLangType' => 'en',
                            'translatedText' => 'Hello',
                        ],
                    ],
                ], 200)
                ->push([
                    'langCode' => 'ko',
                    'confidence' => 0.9,
                ], 200)
                ->push(['errorMessage' => 'API Error'], 500),
        ]);

        $texts = ['안녕하세요', '테스트'];
        $results = $this->service->translateBatch($texts, 'en');

        $this->assertCount(2, $results);
        $this->assertEquals('Hello', $results[0]['translatedText']);
        // Second translation should fallback to original
        $this->assertEquals('테스트', $results[1]['translatedText']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_for_unsupported_target_language(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Target language 'xx' is not supported");

        $this->service->translate('Hello', 'xx', 'en');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_for_unsupported_source_language(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Source language 'yy' is not supported");

        $this->service->translate('Hello', 'en', 'yy');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_null_when_service_is_disabled(): void
    {
        Config::set('services.naver.papago.enabled', false);
        $service = new PapagoService();

        $this->assertNull($service->translate('Hello', 'ko', 'en'));
        $this->assertNull($service->detectLanguage('Hello'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_empty_array_for_batch_when_disabled(): void
    {
        Config::set('services.naver.papago.enabled', false);
        $service = new PapagoService();

        $result = $service->translateBatch(['Hello', 'World'], 'ko');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_check_language_support(): void
    {
        $this->assertTrue($this->service->isLanguageSupported('ko'));
        $this->assertTrue($this->service->isLanguageSupported('en'));
        $this->assertTrue($this->service->isLanguageSupported('ja'));
        $this->assertFalse($this->service->isLanguageSupported('xx'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_provides_list_of_supported_languages(): void
    {
        $languages = $this->service->getSupportedLanguages();

        $this->assertIsArray($languages);
        $this->assertContains('ko', $languages);
        $this->assertContains('en', $languages);
        $this->assertContains('ja', $languages);
        $this->assertContains('zh-CN', $languages);
        $this->assertCount(15, $languages);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_authentication_headers(): void
    {
        Http::fake();

        try {
            $this->service->translate('Hello', 'ko', 'en');
        } catch (\Exception $e) {
            // Ignore exceptions
        }

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-NCP-APIGW-API-KEY-ID', 'test_papago_id') &&
                   $request->hasHeader('X-NCP-APIGW-API-KEY', 'test_papago_secret');
        });
    }
}
