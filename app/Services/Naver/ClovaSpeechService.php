<?php

namespace App\Services\Naver;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * NAVER Clova Speech (STT) Service
 *
 * Documentation: https://api.ncloud-docs.com/docs/ai-naver-clovaspeech
 *
 * Provides Speech-to-Text (STT) for:
 * - Converting audio recordings to text
 * - Multi-language support
 * - Voice memos and travel notes transcription
 *
 * Authentication: X-CLOVASPEECH-API-KEY + custom invoke URL
 */
class ClovaSpeechService extends NaverBaseService
{
    private string $secretKey;
    private string $url;

    public function __construct()
    {
        $config = config('services.naver.speech');
        parent::__construct($config);

        $this->secretKey = $config['secret_key'] ?? '';
        $this->url = $config['url'] ?? '';
    }

    public function isEnabled(): bool
    {
        return !empty($this->secretKey) && !empty($this->url);
    }

    /**
     * Convert speech audio file to text
     *
     * @param UploadedFile|string $audio Audio file or path
     * @param string $language Language code (ko, ja, en, zh)
     * @return array{text: string, confidence: float}|null
     */
    public function speechToText($audio, string $language = 'ko'): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        // Get audio content
        if ($audio instanceof UploadedFile) {
            $audioContent = file_get_contents($audio->getRealPath());
            $audioName = $audio->getClientOriginalName();
        } elseif (is_string($audio)) {
            $audioContent = Storage::get($audio);
            $audioName = basename($audio);
        } else {
            throw new \InvalidArgumentException('Audio must be UploadedFile or file path');
        }

        $this->logApiCall('POST', 'speech-to-text', [
            'audio_name' => $audioName,
            'audio_size' => strlen($audioContent),
            'language' => $language,
        ]);

        $response = $this->client()
            ->withHeaders([
                'X-CLOVASPEECH-API-KEY' => $this->secretKey,
                'Content-Type' => 'application/octet-stream',
            ])
            ->withBody($audioContent, 'application/octet-stream')
            ->post($this->url, [
                'lang' => $language,
            ]);

        $data = $this->handleResponse($response, 'speech-to-text');

        return $this->parseSttResult($data);
    }

    /**
     * Parse STT API response
     */
    private function parseSttResult(array $data): array
    {
        $text = $data['text'] ?? '';
        $confidence = $data['confidence'] ?? 0;

        return [
            'text' => $text,
            'confidence' => (float) $confidence,
        ];
    }

    /**
     * Get supported languages for STT
     */
    public function getSupportedLanguages(): array
    {
        return ['ko', 'ja', 'en', 'zh'];
    }

    /**
     * Check if language is supported
     */
    public function isLanguageSupported(string $language): bool
    {
        return in_array($language, $this->getSupportedLanguages(), true);
    }
}
