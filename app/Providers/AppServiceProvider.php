<?php

namespace App\Providers;

use App\Models\AgentAction;
use App\Models\AgentWebhook;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\TripRecommendation;
use App\Models\UserPreference;
use App\Policies\AgentActionPolicy;
use App\Policies\AgentWebhookPolicy;
use App\Policies\ChatMessagePolicy;
use App\Policies\ChatSessionPolicy;
use App\Policies\TripRecommendationPolicy;
use App\Policies\UserPreferencePolicy;
use App\Services\Naver\ClovaOcrService;
use App\Services\Naver\ClovaSpeechService;
use App\Services\Naver\NaverMapsService;
use App\Services\Naver\PapagoService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register NAVER Cloud Platform services as singletons
        $this->app->singleton(NaverMapsService::class, function () {
            return new NaverMapsService();
        });

        $this->app->singleton(PapagoService::class, function () {
            return new PapagoService();
        });

        $this->app->singleton(ClovaOcrService::class, function () {
            return new ClovaOcrService();
        });

        $this->app->singleton(ClovaSpeechService::class, function () {
            return new ClovaSpeechService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register AI Agent policies
        Gate::policy(ChatSession::class, ChatSessionPolicy::class);
        Gate::policy(ChatMessage::class, ChatMessagePolicy::class);
        Gate::policy(TripRecommendation::class, TripRecommendationPolicy::class);
        Gate::policy(AgentAction::class, AgentActionPolicy::class);
        Gate::policy(UserPreference::class, UserPreferencePolicy::class);
        Gate::policy(AgentWebhook::class, AgentWebhookPolicy::class);
    }
}
