<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Review Sentiment Model
 *
 * Speichert die AI-basierte Sentiment-Analyse für verschiedene Kategorien eines Reviews
 * Kategorien: service, quality, price, friendliness, professionalism, speed, communication,
 *             cleanliness, reliability, location, ambience, recommendation
 * Sentiments: positive, neutral, negative
 */
class ReviewSentiment extends Model
{
    protected $fillable = [
        'review_id',
        'category',
        'sentiment',
        'confidence',
        'excerpt',
    ];

    protected $casts = [
        'confidence' => 'float',
    ];

    /**
     * Review Beziehung
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Ist das Sentiment positiv?
     */
    public function isPositive(): bool
    {
        return $this->sentiment === 'positive';
    }

    /**
     * Ist das Sentiment negativ?
     */
    public function isNegative(): bool
    {
        return $this->sentiment === 'negative';
    }

    /**
     * Ist das Sentiment neutral?
     */
    public function isNeutral(): bool
    {
        return $this->sentiment === 'neutral';
    }

    /**
     * Hole Kategorie-Details aus Config
     */
    public function getCategoryDetails(): array
    {
        $categories = config('review_categories.categories');
        return $categories[$this->category] ?? [];
    }

    /**
     * Hole Sentiment-Details aus Config
     */
    public function getSentimentDetails(): array
    {
        $sentiments = config('review_categories.sentiments');
        return $sentiments[$this->sentiment] ?? [];
    }
}
