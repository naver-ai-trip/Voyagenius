<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    /** @use HasFactory<\Database\Factories\TranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'source_type',
        'source_text',
        'source_language',
        'translated_text',
        'target_language',
        'file_path',
        'blocks',
    ];

    protected $casts = [
        'blocks' => 'array',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class);
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSourceType($query, $type)
    {
        return $query->where('source_type', $type);
    }

    public function scopeForLanguagePair($query, $sourceLanguage, $targetLanguage)
    {
        return $query->where('source_language', $sourceLanguage)
                     ->where('target_language', $targetLanguage);
    }
}
