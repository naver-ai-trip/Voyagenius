<?php

namespace App\Providers;

use App\Events\ActionCompleted;
use App\Events\MessageSent;
use App\Events\RecommendationCreated;
use App\Listeners\TriggerAgentWebhook;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // AI Agent Events → Webhook Triggers
        MessageSent::class => [
            TriggerAgentWebhook::class . '@handleMessageSent',
        ],
        RecommendationCreated::class => [
            TriggerAgentWebhook::class . '@handleRecommendationCreated',
        ],
        ActionCompleted::class => [
            TriggerAgentWebhook::class . '@handleActionCompleted',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
