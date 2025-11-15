<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'provider',
        'provider_id',
        'avatar_path',
        'trip_style',
        'naver_id',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the trips created by this user.
     */
    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Get the trips this user is participating in.
     */
    public function participatingTrips()
    {
        return $this->belongsToMany(Trip::class, 'trip_participants')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get the user's notifications.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the user's translations.
     */
    public function translations()
    {
        return $this->hasMany(Translation::class);
    }

    /**
     * Get the user's reviews.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the user's favorites.
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get the checkpoint images uploaded by this user.
     */
    public function checkpointImages()
    {
        return $this->hasMany(CheckpointImage::class);
    }

    /**
     * Get the user's chat sessions.
     */
    public function chatSessions()
    {
        return $this->hasMany(ChatSession::class);
    }

    /**
     * Get the user's active chat session.
     */
    public function activeChatSession()
    {
        return $this->hasOne(ChatSession::class)->where('is_active', true)->latest('started_at');
    }

    /**
     * Get the user's preferences.
     */
    public function preferences()
    {
        return $this->hasMany(UserPreference::class);
    }

    /**
     * Get the user's trip recommendations.
     */
    public function tripRecommendations()
    {
        return $this->hasMany(TripRecommendation::class);
    }

    /**
     * Get the user's registered webhooks.
     */
    public function webhooks()
    {
        return $this->hasMany(AgentWebhook::class);
    }

    /**
     * Get active webhooks for this user.
     */
    public function activeWebhooks()
    {
        return $this->hasMany(AgentWebhook::class)->where('is_active', true);
    }
}


