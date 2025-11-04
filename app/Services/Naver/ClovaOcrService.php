<?php

namespace App\Services\Naver;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * NAVER Clova OCR Service
 *
 * Documentation: https://api.ncloud-docs.com/docs/ai-naver-clovaocr
 *
 * Provides OCR (Optical Character Recognition) for:
 * - Extracting text from images (menus, signs, receipts)
 * - Multi-language support (Korean, English, Japanese, Chinese)
 */
class ClovaOcrService extends NaverBaseService
{
    private string $secretKey;
    private string $url;

    public function __construct()
    {
        $config = config('services.naver.ocr');
        parent::__construct($config);

        $this->secretKey = $config['secret_key'] ?? '';
        $this->url = $config['url'] ?? '';
    }

    public function isEnabled(): bool
    {
        return !empty($this->secretKey) && !empty($this->url);
    }

    /**
     * Extract text from an image file
     *
     * @param UploadedFile|string $image Image file or path
     * @return array{text: string, fields: array, language: string}|null
     */
    public function extractText($image): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        // Get image content
        if ($image instanceof UploadedFile) {
            $imageContent = file_get_contents($image->getRealPath());
            $imageName = $image->getClientOriginalName();
        } elseif (is_string($image)) {
            $imageContent = Storage::get($image);
            $imageName = basename($image);
        } else {
            throw new \InvalidArgumentException('Image must be UploadedFile or file path');
        }

        $this->logApiCall('POST', 'ocr', [
            'image_name' => $imageName,
            'image_size' => strlen($imageContent),
        ]);

        // Prepare request payload
        $message = [
            'version' => 'V2',
            'requestId' => uniqid('ocr_', true),
            'timestamp' => time() * 1000,
            'images' => [
                [
                    'format' => $this->getImageFormat($imageName),
                    'name' => $imageName,
                ],
            ],
        ];

        $response = $this->client()
            ->withHeaders([
                'X-OCR-SECRET' => $this->secretKey,
                'Content-Type' => 'application/json',
            ])
            ->withBody(json_encode([
                'message' => json_encode($message),
                'file' => base64_encode($imageContent),
            ]), 'application/json')
            ->post($this->url);

        $data = $this->handleResponse($response, 'ocr');

        return $this->parseOcrResult($data);
    }

    /**
     * Parse OCR API response into structured data
     */
    private function parseOcrResult(array $data): array
    {
        $images = $data['images'] ?? [];

        if (empty($images)) {
            return [
                'text' => '',
                'fields' => [],
                'language' => 'unknown',
            ];
        }

        $image = $images[0];
        $fields = $image['fields'] ?? [];

        // Combine all detected text
        $allText = [];
        $structuredFields = [];

        foreach ($fields as $field) {
            $text = $field['inferText'] ?? '';
            $allText[] = $text;

            $structuredFields[] = [
                'text' => $text,
                'confidence' => $field['inferConfidence'] ?? 0,
                'boundingBox' => $field['boundingPoly']['vertices'] ?? [],
                'lineBreak' => $field['lineBreak'] ?? false,
            ];
        }

        return [
            'text' => implode(' ', $allText),
            'fields' => $structuredFields,
            'language' => $this->detectLanguageFromText(implode(' ', $allText)),
        ];
    }

    /**
     * Detect image format from filename
     */
    private function getImageFormat(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'jpg',
            'png' => 'png',
            'pdf' => 'pdf',
            default => 'jpg',
        };
    }

    /**
     * Simple language detection from extracted text
     */
    private function detectLanguageFromText(string $text): string
    {
        $patterns = [
            'ko' => '/[\x{AC00}-\x{D7AF}]/u',      // Korean
            'ja' => '/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', // Japanese
            'zh' => '/[\x{4E00}-\x{9FFF}]/u',      // Chinese
        ];

        foreach ($patterns as $lang => $pattern) {
            if (preg_match($pattern, $text)) {
                return $lang;
            }
        }

        return 'en';
    }
}
