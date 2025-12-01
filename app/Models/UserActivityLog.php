<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * USER ACTIVITY LOG MODEL
 * ========================
 *
 * Speichert alle Änderungen an Benutzern.
 *
 * Verwendung:
 * -----------
 * UserActivityLog::log(
 *     performedBy: auth()->user(),
 *     targetUser: $user,
 *     action: 'plan_changed',
 *     changes: ['old_plan' => 'Free', 'new_plan' => 'Pro']
 * );
 *
 * Queries:
 * --------
 * // Alle Logs eines Users
 * $logs = UserActivityLog::where('target_user_id', $user->id)->latest()->get();
 *
 * // Alle Plan-Wechsel
 * $planChanges = UserActivityLog::where('action', 'plan_changed')->latest()->get();
 *
 * // Logs der letzten 30 Tage
 * $recent = UserActivityLog::where('created_at', '>=', now()->subDays(30))->get();
 */
class UserActivityLog extends Model
{
    // Keine updated_at Spalte
    public $timestamps = false;

    protected $fillable = [
        'performed_by_user_id',
        'target_user_id',
        'action',
        'changes',
        'ip_address',
        'user_agent',
        'description',
        'created_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * SECURITY: Logs sind IMMUTABLE (unveränderlich)
     * ==============================================
     *
     * Audit-Logs dürfen NIEMALS geändert oder gelöscht werden!
     *
     * Gründe:
     * - Compliance (DSGVO, Audit-Anforderungen)
     * - Forensik (bei Security-Incidents)
     * - Vertrauen (User vertrauen auf Historie)
     *
     * Diese Methoden verhindern Updates/Deletes über Eloquent:
     */

    /**
     * Verhindert Updates
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \Exception('Activity Logs können nicht geändert werden! (Immutable)');
    }

    /**
     * Verhindert Deletes
     */
    public function delete(): bool
    {
        throw new \Exception('Activity Logs können nicht gelöscht werden! (Immutable)');
    }

    /**
     * Verhindert Force Deletes
     */
    public function forceDelete(): bool
    {
        throw new \Exception('Activity Logs können nicht gelöscht werden! (Immutable)');
    }

    /**
     * User der die Aktion durchgeführt hat
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    /**
     * User an dem die Aktion durchgeführt wurde
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Helper: Log erstellen
     *
     * @param User|null $performedBy Wer hat die Aktion durchgeführt? (NULL = System)
     * @param User $targetUser An wem wurde die Aktion durchgeführt?
     * @param string $action Art der Aktion (created, updated, plan_changed, etc.)
     * @param array|null $changes Änderungs-Details
     * @param string|null $description Optionale Beschreibung
     */
    public static function log(
        ?User $performedBy,
        User $targetUser,
        string $action,
        ?array $changes = null,
        ?string $description = null
    ): self {
        return self::create([
            'performed_by_user_id' => $performedBy?->id,
            'target_user_id' => $targetUser->id,
            'action' => $action,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
