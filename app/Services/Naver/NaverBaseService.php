<?php

namespace App\Services\Naver;

use App\Exceptions\NaverApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Base class for NAVER Cloud Platform API services
 *
 * Provides common functionality for all NAVER API integrations:
 * - Authentication headers
 * - Error handling
 * - Retry logic
 * - Logging
 */
abstract class NaverBaseService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retrySleep;
    protected bool $enabled;

    public function __construct(array $config)
    {
        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
        $this->baseUrl = $config['base_url'] ?? '';
        $this->timeout = config('services.naver.timeout', 30);
        $this->retryTimes = config('services.naver.retry_times', 3);
        $this->retrySleep = config('services.naver.retry_sleep', 1000);
        $this->enabled = $config['enabled'] ?? true;
    }

    /**
     * Check if service is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Create HTTP client with NAVER authentication headers
     */
    protected function client(): PendingRequest
    {
        $client = Http::withHeaders([
            'X-NCP-APIGW-API-KEY-ID' => $this->clientId,
            'X-NCP-APIGW-API-KEY' => $this->clientSecret,
            'Content-Type' => 'application/json',
        ])
        ->timeout($this->timeout)
        ->retry($this->retryTimes, $this->retrySleep, throw: false);
        
        // Set base URL if provided
        if (!empty($this->baseUrl)) {
            $client = $client->baseUrl($this->baseUrl);
        }
        
        return $client;
    }

    /**
     * Handle API response and errors
     */
    protected function handleResponse(Response $response, string $context = ''): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $errorMessage = $response->json('errorMessage') ?? $response->body();
        
        // If error message is empty, use default
        if (empty($errorMessage)) {
            $errorMessage = 'Unknown error';
        }

        $error = [
            'status' => $response->status(),
            'message' => $errorMessage,
            'context' => $context,
        ];

        Log::error('NAVER API Error', $error);

        throw new NaverApiException(
            "NAVER API Error ({$context}): " . $errorMessage,
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
            Log::debug('NAVER API Call', [
                'service' => static::class,
                'method' => $method,
                'endpoint' => $endpoint,
                'params' => $params,
            ]);
        }
    }
}
