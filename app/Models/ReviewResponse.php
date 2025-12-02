<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReviewResponse Model
 *
 * Speichert Antworten auf Reviews
 *
 * Flow:
 * 1. User schreibt Antwort im Frontend
 * 2. ReviewResponse wird in DB gespeichert (is_published = false)
 * 3. Antwort wird an Plattform-API gesendet
 * 4. Falls erfolgreich: is_published = true, published_at = now()
 *
 * WICHTIG: Eine Antwort kann auch lokal gespeichert sein ohne published zu sein!
 * Das ermöglicht "Draft"-Antworten oder Retry bei API-Fehlern.
 */
class ReviewResponse extends Model
{
    use HasFactory;
    protected $fillable = [
        'review_id',
        'user_id',
        'text',
        'sent_at',
        'provider_response_id',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Beziehung: Antwort gehört zu einem Review
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Beziehung: Antwort gehört zu einem User (Autor)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
