<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Review Model
 *
 * Speichert Reviews von verschiedenen Plattformen (Google, Trustpilot, etc.)
 * Jeder Review gehört zu einem User und einer verbundenen Plattform.
 */
class Review extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'connected_platform_id',
        'provider_review_id',
        'rating',
        'text',
        'reviewer_name',
        'reviewer_photo_url',
        'review_date',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array', // JSON -> Array
        'review_date' => 'datetime',
        'rating' => 'integer',
    ];

    /**
     * Beziehung: Review gehört zu einem User (Owner)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Beziehung: Review stammt von einer verbundenen Plattform
     */
    public function connectedPlatform(): BelongsTo
    {
        return $this->belongsTo(ConnectedPlatform::class);
    }

    /**
     * Beziehung: Review kann mehrere Antworten haben
     */
    public function responses(): HasMany
    {
        return $this->hasMany(ReviewResponse::class);
    }

    /**
     * Beziehung: Review hat Sentiment-Analysen für verschiedene Kategorien
     */
    public function sentiments(): HasMany
    {
        return $this->hasMany(ReviewSentiment::class);
    }

    /**
     * Hole alle negativen Sentiments (Probleme/Kritik)
     */
    public function negativesentiments(): HasMany
    {
        return $this->hasMany(ReviewSentiment::class)->where('sentiment', 'negative');
    }

    /**
     * Hole alle positiven Sentiments
     */
    public function positiveSentiments(): HasMany
    {
        return $this->hasMany(ReviewSentiment::class)->where('sentiment', 'positive');
    }

    /**
     * Hat dieses Review Probleme/Kritik in irgendeiner Kategorie?
     */
    public function hasProblems(): bool
    {
        return $this->sentiments()->where('sentiment', 'negative')->exists();
    }

    /**
     * Hole Sentiment für eine bestimmte Kategorie
     */
    public function getSentimentForCategory(string $category): ?ReviewSentiment
    {
        return $this->sentiments()->where('category', $category)->first();
    }
}
