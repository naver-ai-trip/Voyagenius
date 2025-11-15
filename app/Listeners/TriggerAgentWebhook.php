<?php

namespace App\Listeners;

use App\Events\ActionCompleted;
use App\Events\MessageSent;
use App\Events\RecommendationCreated;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Webhook Trigger Listener
 * 
 * Listens to broadcast events and triggers registered webhooks.
 * Runs asynchronously via queue to avoid blocking event processing.
 */
class TriggerAgentWebhook implements ShouldQueue
{
    use InteractsWithQueue;

    protected WebhookService $webhookService;

    /**
     * Create the event listener.
     */
    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle MessageSent event
     */
    public function handleMessageSent(MessageSent $event): void
    {
        $this->webhookService->trigger(
            'message.sent',
            [
                'id' => $event->message->id,
                'chat_session_id' => $event->message->chat_session_id,
                'from_role' => $event->message->from_role,
                'message' => $event->message->message,
                'created_at' => $event->message->created_at->toIso8601String(),
            ],
            $event->message->chatSession->user_id
        );
    }

    /**
     * Handle RecommendationCreated event
     */
    public function handleRecommendationCreated(RecommendationCreated $event): void
    {
        $this->webhookService->trigger(
            'recommendation.created',
            [
                'id' => $event->recommendation->id,
                'trip_id' => $event->recommendation->trip_id,
                'recommendation_type' => $event->recommendation->recommendation_type,
                'confidence_score' => $event->recommendation->confidence_score,
                'created_at' => $event->recommendation->created_at->toIso8601String(),
            ],
            $event->recommendation->trip->user_id
        );
    }

    /**
     * Handle ActionCompleted event
     */
    public function handleActionCompleted(ActionCompleted $event): void
    {
        $this->webhookService->trigger(
            'action.completed',
            [
                'id' => $event->action->id,
                'chat_session_id' => $event->action->chat_session_id,
                'action_type' => $event->action->action_type,
                'status' => $event->action->status,
                'completed_at' => $event->action->completed_at?->toIso8601String(),
            ],
            $event->action->chatSession->user_id
        );
    }
}
