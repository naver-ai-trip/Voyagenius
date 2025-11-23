<?php

namespace App\Services\Amadeus;

use App\Exceptions\AmadeusApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Base class for Amadeus API services
 *
 * Provides common functionality for all Amadeus API integrations:
 * - OAuth2 authentication
 * - Token management
 * - Error handling
 * - Retry logic
 * - Logging
 */
abstract class AmadeusService
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $baseUrl;
    protected string $tokenUrl;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retrySleep;
    protected bool $enabled;
    protected string $tokenCacheKey = 'amadeus_access_token';

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->apiSecret = $config['api_secret'] ?? '';
        $this->baseUrl = $config['base_url'] ?? 'https://test.api.amadeus.com/v1';
        $this->tokenUrl = $config['token_url'] ?? 'https://test.api.amadeus.com/v1/security/oauth2/token';
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
        return $this->enabled && !empty($this->apiKey) && !empty($this->apiSecret);
    }

    /**
     * Get access token (cached or new)
     */
    protected function getAccessToken(): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }

        // Check if token is cached
        $token = Cache::get($this->tokenCacheKey);

        if ($token) {
            return $token;
        }

        // Get new token
        return $this->requestAccessToken();
    }

    /**
     * Request new access token from Amadeus OAuth2
     */
    protected function requestAccessToken(): ?string
    {
        try {
            $response = Http::asForm()
                ->timeout($this->timeout)
                ->post($this->tokenUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->apiKey,
                    'client_secret' => $this->apiSecret,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'] ?? null;
                $expiresIn = $data['expires_in'] ?? 1799; // Default 30 minutes - 1 second

                if ($token) {
                    // Cache token for slightly less than expiration time
                    Cache::put($this->tokenCacheKey, $token, $expiresIn - 60);
                    return $token;
                }
            }

            Log::error('Amadeus OAuth2 Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Amadeus OAuth2 Exception', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create HTTP client with Amadeus authentication
     *
     * @param string|null $version API version (v1, v2, v3) - if null, uses baseUrl as-is
     */
    protected function client(?string $version = null): ?PendingRequest
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return null;
        }

        $baseUrl = $this->baseUrl;

        // If version is specified and different from base URL version, adjust
        if ($version && $version !== 'v1') {
            // Replace /v1 with the specified version in baseUrl
            $baseUrl = preg_replace('/\/v\d+$/', '/' . $version, $this->baseUrl);
        }

        return Http::withToken($token)
            ->baseUrl($baseUrl)
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retrySleep, throw: false);
    }

    /**
     * Handle API response and errors
     */
    protected function handleResponse(Response $response, string $context = ''): ?array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $errorData = $response->json();
        $errorMessage = $errorData['errors'][0]['detail'] ??
                       $errorData['error_description'] ??
                       $response->body();

        $error = [
            'status' => $response->status(),
            'message' => $errorMessage,
            'context' => $context,
            'errors' => $errorData['errors'] ?? [],
        ];

        Log::error('Amadeus API Error', $error);

        throw new AmadeusApiException(
            "Amadeus API Error ({$context}): " . $errorMessage,
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
            Log::debug('Amadeus API Call', [
                'service' => static::class,
                'method' => $method,
                'endpoint' => $endpoint,
                'params' => $params,
            ]);
        }
    }
}

