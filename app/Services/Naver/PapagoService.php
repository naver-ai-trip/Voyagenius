<?php

namespace App\Services\Naver;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * NAVER Cloud Platform Papago Translation API Service
 *
 * Documentation: https://api.ncloud-docs.com/docs/ai-naver-papagonmt
 * Base URL: https://papago.apigw.ntruss.com
 * Authentication: NCP Gateway (X-NCP-APIGW-API-KEY-ID / X-NCP-APIGW-API-KEY)
 *
 * Provides integration with Papago for:
 * - Text translation between 15+ languages
 * - Language detection
 *
 * Supported languages: ko, en, ja, zh-CN, zh-TW, vi, id, th, de, ru, es, it, fr, hi, pt
 */
class PapagoService extends NaverBaseService
{
    private const SUPPORTED_LANGUAGES = [
        'ko', 'en', 'ja', 'zh-CN', 'zh-TW', 'vi', 'id', 'th', 'de', 'ru', 'es', 'it', 'fr', 'hi', 'pt'
    ];

    public function __construct()
    {
        parent::__construct(config('services.naver.papago'));
    }

    /**
     * Translate text from source language to target language
     *
     * @param string $text Text to translate (max 5000 characters)
     * @param string $targetLang Target language code (e.g., 'en', 'ko', 'ja')
     * @param string|null $sourceLang Source language code (auto-detect if null)
     * @return array{translatedText: string, sourceLang: string, targetLang: string}|null
     */
    public function translate(string $text, string $targetLang, ?string $sourceLang = null): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        if (!$this->isLanguageSupported($targetLang)) {
            throw new \InvalidArgumentException("Target language '{$targetLang}' is not supported");
        }

        // Auto-detect source language if not provided
        if ($sourceLang === null) {
            $detected = $this->detectLanguage($text);
            $sourceLang = $detected['langCode'] ?? 'ko';
        }

        if (!$this->isLanguageSupported($sourceLang)) {
            throw new \InvalidArgumentException("Source language '{$sourceLang}' is not supported");
        }

        // No translation needed if source and target are the same
        if ($sourceLang === $targetLang) {
            return [
                'translatedText' => $text,
                'sourceLang' => $sourceLang,
                'targetLang' => $targetLang,
            ];
        }

        $this->logApiCall('POST', 'translate', [
            'text_length' => strlen($text),
            'source' => $sourceLang,
            'target' => $targetLang,
        ]);

        $response = $this->client()
            ->post('/nmt/v1/translation', [
                'source' => $sourceLang,
                'target' => $targetLang,
                'text' => $text,
            ]);

        $data = $this->handleResponse($response, 'translate');

        return [
            'translatedText' => $data['message']['result']['translatedText'] ?? '',
            'sourceLang' => $data['message']['result']['srcLangType'] ?? $sourceLang,
            'targetLang' => $data['message']['result']['tarLangType'] ?? $targetLang,
        ];
    }

    /**
     * Detect the language of the given text
     *
     * @param string $text Text to analyze
     * @return array{langCode: string, confidence: float}|null
     */
    public function detectLanguage(string $text): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $this->logApiCall('POST', 'detect-language', [
            'text_length' => strlen($text),
        ]);

        $response = $this->client()
            ->post('/langs/v1/dect', [
                'query' => $text,
            ]);

        $data = $this->handleResponse($response, 'detect-language');

        return [
            'langCode' => $data['langCode'] ?? 'unknown',
            'confidence' => (float) ($data['confidence'] ?? 0),
        ];
    }

    /**
     * Translate multiple texts in batch
     *
     * @param array<string> $texts Array of texts to translate
     * @param string $targetLang Target language code
     * @param string|null $sourceLang Source language code (auto-detect if null)
     * @return array<array{translatedText: string, sourceLang: string, targetLang: string}>
     */
    public function translateBatch(array $texts, string $targetLang, ?string $sourceLang = null): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $results = [];

        foreach ($texts as $text) {
            try {
                $result = $this->translate($text, $targetLang, $sourceLang);
                if ($result) {
                    $results[] = $result;
                }
            } catch (\Exception $e) {
                // Log error and continue with next text
                Log::warning('Papago batch translation failed for text', [
                    'text' => substr($text, 0, 50),
                    'error' => $e->getMessage(),
                ]);
                $results[] = [
                    'translatedText' => $text, // Fallback to original
                    'sourceLang' => $sourceLang ?? 'unknown',
                    'targetLang' => $targetLang,
                ];
            }
        }

        return $results;
    }

    /**
     * Check if language code is supported
     */
    public function isLanguageSupported(string $langCode): bool
    {
        return in_array($langCode, self::SUPPORTED_LANGUAGES, true);
    }

    /**
     * Get list of supported languages
     */
    public function getSupportedLanguages(): array
    {
        return self::SUPPORTED_LANGUAGES;
    }

    /**
     * Translate text from image using Papago Image Translation API
     *
     * This method combines OCR and translation in a single API call,
     * which is more efficient than calling OCR + translation separately.
     *
     * @param mixed $image UploadedFile or file path
     * @param string $targetLang Target language code (e.g., 'en', 'ko', 'ja')
     * @param string|null $sourceLang Source language code (auto-detect if null)
     * @return array{translatedText: string, detectedText: string, sourceLang: string, targetLang: string}|null
     */
    public function translateImage($image, string $targetLang, ?string $sourceLang = null): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        if (!$this->isLanguageSupported($targetLang)) {
            throw new \InvalidArgumentException("Target language '{$targetLang}' is not supported");
        }

        // Auto-detect source language if not provided
        if ($sourceLang === null) {
            $sourceLang = 'auto';
        } elseif (!$this->isLanguageSupported($sourceLang)) {
            throw new \InvalidArgumentException("Source language '{$sourceLang}' is not supported");
        }

        // Prepare image file for upload
        if ($image instanceof \Illuminate\Http\UploadedFile) {
            $imagePath = $image->getRealPath();
            $imageContents = file_get_contents($imagePath);
        } else {
            $imageContents = file_get_contents($image);
        }

        $this->logApiCall('POST', 'translate-image', [
            'image_size' => strlen($imageContents),
            'source' => $sourceLang,
            'target' => $targetLang,
        ]);

        // Use multipart/form-data for image upload
        // Create a fresh client without Content-Type header to allow multipart
        $response = Http::withHeaders([
            'X-NCP-APIGW-API-KEY-ID' => $this->clientId,
            'X-NCP-APIGW-API-KEY' => $this->clientSecret,
        ])
        ->baseUrl($this->baseUrl)
        ->timeout($this->timeout)
        ->retry($this->retryTimes, $this->retrySleep, throw: false)
        ->asMultipart()
        ->attach('image', $imageContents, 'image.jpg')
        ->attach('source', $sourceLang)
        ->attach('target', $targetLang)
        ->post('/image-to-text/v1/translate');

        $data = $this->handleResponse($response, 'translate-image');

        // Extract OCR text and translation from response
        // The actual API response structure is: { data: { sourceText, targetText, sourceLang, targetLang, blocks: [...] } }
        $detectedText = $data['data']['sourceText'] ?? '';
        $translatedText = $data['data']['targetText'] ?? '';
        $detectedSourceLang = $data['data']['sourceLang'] ?? $sourceLang;
        $blocks = $data['data']['blocks'] ?? [];

        return [
            'translatedText' => $translatedText,
            'detectedText' => $detectedText,
            'sourceLang' => $detectedSourceLang,
            'targetLang' => $targetLang,
            'blocks' => $blocks,
        ];
    }
}
