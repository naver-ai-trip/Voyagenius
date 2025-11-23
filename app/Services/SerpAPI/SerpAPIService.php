<?php

namespace App\Services\SerpAPI;

use App\Exceptions\SerpApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Base class for SerpAPI services
 *
 * Provides common functionality for all SerpAPI integrations:
 * - API key authentication
 * - Error handling
 * - Retry logic
 * - Logging
 *
 * @see https://serpapi.com/
 */
abstract class SerpAPIService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retrySleep;
    protected bool $enabled;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->baseUrl = $config['base_url'] ?? 'https://serpapi.com/search.json';
        $this->timeout = $config['timeout'] ?? 30;
        $this->retryTimes = $config['retry_times'] ?? 3;
        $this->retrySleep = $config['retry_sleep'] ?? 1000;
        $this->enabled = $config['enabled'] ?? true;
    }

    /**
     * Check if service is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->apiKey);
    }

    /**
     * Create HTTP client for SerpAPI requests
     */
    protected function client(): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->retry($this->retryTimes, $this->retrySleep, throw: false);
    }

    /**
     * Build query parameters with API key
     */
    protected function buildParams(array $params): array
    {
        return array_merge($params, [
            'api_key' => $this->apiKey,
        ]);
    }

    /**
     * Handle API response and errors
     */
    protected function handleResponse(Response $response, string $context = ''): ?array
    {
        if ($response->successful()) {
            $data = $response->json() ?? [];

            // Check for SerpAPI error in response
            if (isset($data['error'])) {
                $errorMessage = $data['error'] ?? 'Unknown SerpAPI error';

                $error = [
                    'status' => $response->status(),
                    'message' => $errorMessage,
                    'context' => $context,
                ];

                Log::error('SerpAPI Error', $error);

                throw new SerpApiException(
                    "SerpAPI Error ({$context}): " . $errorMessage,
                    $response->status(),
                    $error
                );
            }

            return $data;
        }

        $errorData = $response->json();
        $errorMessage = $errorData['error'] ??
            $errorData['message'] ??
            $response->body() ??
            'Unknown error';

        $error = [
            'status' => $response->status(),
            'message' => $errorMessage,
            'context' => $context,
            'errors' => $errorData['errors'] ?? [],
        ];

        Log::error('SerpAPI Error', $error);

        throw new SerpApiException(
            "SerpAPI Error ({$context}): " . $errorMessage,
            $response->status(),
            $error
        );
    }

    /**
     * Log API call for debugging
     */
    protected function logApiCall(string $method, string $endpoint, array $params = []): void
    {
        if (config('app.debug')) {
            // Don't log API key in debug logs
            $safeParams = $params;
            if (isset($safeParams['api_key'])) {
                $safeParams['api_key'] = '***';
            }

            Log::debug('SerpAPI Call', [
                'service' => static::class,
                'method' => $method,
                'endpoint' => $endpoint,
                'params' => $safeParams,
            ]);
        }
    }
}
