<?php

namespace App\Jobs;

use App\Models\TripRecommendation;
use App\Services\NaverMapsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process AI-generated trip recommendations
 * 
 * This job runs asynchronously to generate detailed recommendations
 * using NAVER Maps API and AI analysis.
 */
class ProcessRecommendationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public TripRecommendation $recommendation;
    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(TripRecommendation $recommendation)
    {
        $this->recommendation = $recommendation;
    }

    /**
     * Execute the job.
     */
    public function handle(NaverMapsService $naverMaps): void
    {
        Log::info('Processing recommendation', [
            'recommendation_id' => $this->recommendation->id,
            'type' => $this->recommendation->recommendation_type,
        ]);

        try {
            $this->recommendation->update(['status' => 'processing']);

            // Simulate AI processing with NAVER Maps API integration
            $enrichedData = $this->enrichRecommendationData($naverMaps);

            $this->recommendation->update([
                'status' => 'completed',
                'recommendation_data' => array_merge(
                    $this->recommendation->recommendation_data ?? [],
                    $enrichedData
                ),
            ]);

            Log::info('Recommendation processed successfully', [
                'recommendation_id' => $this->recommendation->id,
            ]);
        } catch (\Exception $e) {
            $this->recommendation->update(['status' => 'failed']);

            Log::error('Recommendation processing failed', [
                'recommendation_id' => $this->recommendation->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Enrich recommendation with NAVER Maps data
     */
    protected function enrichRecommendationData(NaverMapsService $naverMaps): array
    {
        $data = $this->recommendation->recommendation_data ?? [];

        // If recommendation includes place names, get details from NAVER Maps
        if (isset($data['places']) && is_array($data['places'])) {
            $enrichedPlaces = [];

            foreach ($data['places'] as $place) {
                // In production, call NAVER Local Search API
                // For now, add placeholder enrichment
                $enrichedPlaces[] = array_merge($place, [
                    'enriched_at' => now()->toIso8601String(),
                    'data_source' => 'naver_maps',
                ]);
            }

            return ['enriched_places' => $enrichedPlaces];
        }

        return ['processed_at' => now()->toIso8601String()];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Recommendation job failed permanently', [
            'recommendation_id' => $this->recommendation->id,
            'exception' => $exception->getMessage(),
        ]);

        $this->recommendation->update(['status' => 'failed']);
    }
}
