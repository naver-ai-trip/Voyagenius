<?php

namespace App\Jobs;

use App\Services\NaverPapagoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Batch translate content using NAVER Papago
 * 
 * This job processes multiple translation requests
 * efficiently to avoid rate limits.
 */
class TranslateContentBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $items;
    public string $targetLanguage;
    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     * 
     * @param array $items Array of items to translate, each with 'id', 'text', 'source_lang'
     * @param string $targetLanguage Target language code (ko, en, ja, zh-CN, etc.)
     */
    public function __construct(array $items, string $targetLanguage)
    {
        $this->items = $items;
        $this->targetLanguage = $targetLanguage;
    }

    /**
     * Execute the job.
     */
    public function handle(NaverPapagoService $papago): void
    {
        Log::info('Starting batch translation', [
            'items_count' => count($this->items),
            'target_language' => $this->targetLanguage,
        ]);

        $results = [];

        try {
            foreach ($this->items as $item) {
                // Translate using NAVER Papago
                $translation = $papago->translate(
                    $item['text'],
                    $item['source_lang'],
                    $this->targetLanguage
                );

                $results[] = [
                    'id' => $item['id'],
                    'original' => $item['text'],
                    'translated' => $translation['translatedText'] ?? null,
                    'detected_lang' => $translation['detectedLang'] ?? $item['source_lang'],
                ];

                // Rate limiting: sleep 100ms between requests
                if (count($this->items) > 1) {
                    usleep(100000);
                }
            }

            Log::info('Batch translation completed', [
                'items_count' => count($results),
                'target_language' => $this->targetLanguage,
            ]);

            // Dispatch event or store results
            // For now, just log success
        } catch (\Exception $e) {
            Log::error('Batch translation failed', [
                'items_count' => count($this->items),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Translation batch job failed permanently', [
            'items_count' => count($this->items),
            'exception' => $exception->getMessage(),
        ]);
    }
}
