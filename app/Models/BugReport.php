<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BUG REPORT MODEL
 * ================
 *
 * User können Bugs, Feature Requests, etc. melden.
 *
 * Relationships:
 * - belongsTo User (Reporter)
 * - belongsTo User (Assigned Admin)
 *
 * Scopes:
 * - open() - Nur offene Bugs
 * - byPriority() - Nach Priorität sortiert
 * - recent() - Neueste zuerst
 */
class BugReport extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'page_url',
        'browser',
        'os',
        'steps_to_reproduce',
        'admin_notes',
        'assigned_to',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Bug gehört zu einem User (Reporter)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bug ist zugewiesen an einen Admin
     */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope: Nur offene Bugs
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    /**
     * Scope: Nur geschlossene Bugs
     */
    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    /**
     * Scope: Nach Priorität sortiert (Critical → High → Medium → Low)
     */
    public function scopeByPriority($query)
    {
        return $query->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')");
    }

    /**
     * Scope: Neueste zuerst
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Ist Bug offen?
     */
    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress']);
    }

    /**
     * Ist Bug gelöst?
     */
    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    /**
     * Status-Badge Farbe für Frontend
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open' => 'blue',
            'in_progress' => 'yellow',
            'resolved' => 'green',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Priority-Badge Farbe für Frontend
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Type-Label für Frontend
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'bug' => 'Bug',
            'feature' => 'Feature Request',
            'improvement' => 'Verbesserung',
            'question' => 'Frage',
            default => 'Sonstiges',
        };
    }
}
