<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SUBSCRIPTION ACTIVITY LOG MODEL
 * ================================
 *
 * Speichert alle Änderungen an Subscriptions.
 *
 * Verwendung:
 * -----------
 * SubscriptionActivityLog::log(
 *     performedBy: auth()->user(),
 *     targetUser: $user,
 *     plan: $plan,
 *     action: 'subscribed',
 *     changes: ['promo_code' => 'SAVE50', 'price' => 4.99]
 * );
 */
class SubscriptionActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'performed_by_user_id',
        'target_user_id',
        'plan_id',
        'plan_name',
        'action',
        'changes',
        'stripe_subscription_id',
        'ip_address',
        'description',
        'created_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * SECURITY: Logs sind IMMUTABLE (unveränderlich)
     * Siehe UserActivityLog für Details
     */

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \Exception('Activity Logs können nicht geändert werden! (Immutable)');
    }

    public function delete(): bool
    {
        throw new \Exception('Activity Logs können nicht gelöscht werden! (Immutable)');
    }

    public function forceDelete(): bool
    {
        throw new \Exception('Activity Logs können nicht gelöscht werden! (Immutable)');
    }

    /**
     * User/Admin der die Aktion durchgeführt hat
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    /**
     * User dessen Subscription geändert wurde
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Betroffener Plan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Helper: Log erstellen
     */
    public static function log(
        ?User $performedBy,
        User $targetUser,
        ?Plan $plan,
        string $action,
        ?array $changes = null,
        ?string $stripeSubscriptionId = null,
        ?string $description = null
    ): self {
        return self::create([
            'performed_by_user_id' => $performedBy?->id,
            'target_user_id' => $targetUser->id,
            'plan_id' => $plan?->id,
            'plan_name' => $plan?->name,
            'action' => $action,
            'changes' => $changes,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'ip_address' => request()->ip(),
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
