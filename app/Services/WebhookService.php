<?php

namespace App\Services;

use App\Models\AgentWebhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Service
 * 
 * Handles triggering webhooks for external AI agents
 * when events occur in the system.
 */
class WebhookService
{
    /**
     * Trigger webhooks for an event
     * 
     * @param string $eventName Name of the event (e.g., 'message.sent')
     * @param array $payload Event data to send
     * @param int|null $userId Filter webhooks by user ID
     */
    public function trigger(string $eventName, array $payload, ?int $userId = null): void
    {
        $query = AgentWebhook::active()->forEvent($eventName);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $webhooks = $query->get();

        foreach ($webhooks as $webhook) {
            $this->triggerWebhook($webhook, $eventName, $payload);
        }
    }

    /**
     * Trigger a single webhook
     */
    protected function triggerWebhook(AgentWebhook $webhook, string $eventName, array $payload): void
    {
        $webhook->markTriggered();

        $fullPayload = [
            'event' => $eventName,
            'timestamp' => now()->toIso8601String(),
            'data' => $payload,
        ];

        $signature = $webhook->generateSignature($fullPayload);

        try {
            $response = Http::timeout($webhook->timeout_seconds)
                ->retry($webhook->retry_count, 100)
                ->withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $eventName,
                    'User-Agent' => 'TripPlanner-Webhook/1.0',
                ])
                ->post($webhook->url, $fullPayload);

            if ($response->successful()) {
                $webhook->markSuccess();
                
                Log::info('Webhook delivered successfully', [
                    'webhook_id' => $webhook->id,
                    'event' => $eventName,
                    'url' => $webhook->url,
                    'status' => $response->status(),
                ]);
            } else {
                $error = "HTTP {$response->status()}: {$response->body()}";
                $webhook->markFailure($error);
                
                Log::warning('Webhook delivery failed', [
                    'webhook_id' => $webhook->id,
                    'event' => $eventName,
                    'error' => $error,
                ]);
            }
        } catch (\Exception $e) {
            $webhook->markFailure($e->getMessage());
            
            Log::error('Webhook delivery exception', [
                'webhook_id' => $webhook->id,
                'event' => $eventName,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify webhook signature from incoming request
     */
    public function verifyIncomingSignature(AgentWebhook $webhook, string $signature, array $payload): bool
    {
        return $webhook->verifySignature($signature, $payload);
    }
}
