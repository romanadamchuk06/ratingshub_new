<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PLAN ACTIVITY LOG MODEL
 * ========================
 *
 * Speichert alle Änderungen an Subscription-Plänen.
 *
 * Verwendung:
 * -----------
 * PlanActivityLog::log(
 *     performedBy: auth()->user(),
 *     plan: $plan,
 *     action: 'price_changed',
 *     changes: ['price' => ['old' => 9.99, 'new' => 14.99]]
 * );
 *
 * Queries:
 * --------
 * // Alle Logs eines Plans
 * $logs = PlanActivityLog::where('plan_id', $plan->id)->latest()->get();
 *
 * // Alle Preis-Änderungen
 * $priceChanges = PlanActivityLog::where('action', 'price_changed')->latest()->get();
 */
class PlanActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'performed_by_user_id',
        'plan_id',
        'plan_name',
        'action',
        'changes',
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
     * Admin der die Aktion durchgeführt hat
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    /**
     * Plan an dem die Aktion durchgeführt wurde
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Helper: Log erstellen
     */
    public static function log(
        User $performedBy,
        Plan $plan,
        string $action,
        ?array $changes = null,
        ?string $description = null
    ): self {
        return self::create([
            'performed_by_user_id' => $performedBy->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'action' => $action,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
