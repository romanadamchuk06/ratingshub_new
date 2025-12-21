<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'plan_id',
        'trial_ends_at',
        'ends_grace_period_at',
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
            'is_admin' => 'boolean',
            'trial_ends_at' => 'datetime',
            'ends_grace_period_at' => 'datetime',
        ];
    }

    public function connectedPlatforms(): HasMany
    {
        return $this->hasMany(ConnectedPlatform::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * User hat viele Reviews (von verschiedenen Plattformen)
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * User hat viele Review-Antworten
     */
    public function reviewResponses(): HasMany
    {
        return $this->hasMany(ReviewResponse::class);
    }

    /**
     * User hat ein Business Profile (1:1 Beziehung)
     */
    public function businessProfile()
    {
        return $this->hasOne(BusinessProfile::class);
    }

    /**
     * Check if user is on trial period
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if user's trial has ended
     */
    public function trialExpired(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Start a trial period for the user
     */
    public function startTrial(int $days = 30): void
    {
        $this->update([
            'trial_ends_at' => now()->addDays($days),
        ]);
    }

    /**
     * Check if user can add more platforms
     */
    public function canAddPlatform(): bool
    {
        $currentCount = $this->connectedPlatforms()->count();
        $maxPlatforms = $this->plan->max_platforms ?? 1;

        return $currentCount < $maxPlatforms;
    }

    /**
     * Get remaining platform slots
     */
    public function remainingPlatformSlots(): int
    {
        $currentCount = $this->connectedPlatforms()->count();
        $maxPlatforms = $this->plan->max_platforms ?? 1;

        return max(0, $maxPlatforms - $currentCount);
    }

    /**
     * ========================================
     * SUBSCRIPTION & FEATURE-CHECK HELPERS
     * ========================================
     *
     * Diese Methods prüfen ob User Features nutzen darf.
     * Berücksichtigt: Admin-Status, Subscription-Status, Plan-Features
     */

    /**
     * Kann User AI-Antworten nutzen?
     */
    public function canUseAI(): bool
    {
        // Admin kann immer
        if ($this->is_admin) {
            return true;
        }

        // Kein aktives Abo → Nein
        if (!$this->subscribed('default')) {
            return false;
        }

        // Prüfe ob Plan das Feature hat
        return $this->plan && ($this->plan->features['ai_responses'] ?? false);
    }

    /**
     * Kann User mehrere Team-Mitglieder hinzufügen?
     */
    public function canAddMultipleUsers(): bool
    {
        if ($this->is_admin) {
            return true;
        }

        if (!$this->subscribed('default')) {
            return false;
        }

        return $this->plan && ($this->plan->features['multi_user'] ?? false);
    }

    /**
     * Hat User Zugriff auf Priority-Support?
     */
    public function hasPrioritySupport(): bool
    {
        if ($this->is_admin) {
            return true;
        }

        if (!$this->subscribed('default')) {
            return false;
        }

        return $this->plan && ($this->plan->features['priority_support'] ?? false);
    }

    /**
     * Review-Limit des Users (Anzahl Reviews die importiert werden dürfen)
     */
    public function getReviewLimit(): int
    {
        // Admin hat unbegrenzt
        if ($this->is_admin) {
            return PHP_INT_MAX;
        }

        // Kein Abo → 0 Reviews
        if (!$this->subscribed('default')) {
            return 0;
        }

        // Plan-Limit
        return $this->plan ? ($this->plan->features['review_limit'] ?? 0) : 0;
    }

    /**
     * Wie viele Reviews hat User noch übrig?
     */
    public function remainingReviews(): int
    {
        $limit = $this->getReviewLimit();

        // Unbegrenzt?
        if ($limit === PHP_INT_MAX) {
            return PHP_INT_MAX;
        }

        // Aktuelle Anzahl Reviews
        $current = $this->reviews()->count();

        return max(0, $limit - $current);
    }

    /**
     * Hat User ein aktives Abo? (inkl. Grace Period & Trial)
     */
    public function hasActiveSubscription(): bool
    {
        // Admin hat immer Zugriff
        if ($this->is_admin) {
            return true;
        }

        // Prüfe Trial
        if ($this->onTrial()) {
            return true;
        }

        // Prüfe Subscription (Cashier-Method, inkl. Grace Period!)
        return $this->subscribed('default');
    }
}
