<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CheckpointImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'map_checkpoint_id',
        'user_id',
        'file_path',
        'caption',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    
    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(MapCheckpoint::class, 'map_checkpoint_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    
    public function scopeForCheckpoint($query, int $checkpointId)
    {
        return $query->where('map_checkpoint_id', $checkpointId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('uploaded_at', 'desc');
    }

    /**
     * Accessors
     */
    
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}