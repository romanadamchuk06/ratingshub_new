<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PROMO CODE ACTIVITY LOG MODEL
 * ==============================
 *
 * Speichert alle Änderungen an Promo-Codes.
 *
 * Verwendung:
 * -----------
 * PromoCodeActivityLog::log(
 *     performedBy: auth()->user(),
 *     promoCode: $promoCode,
 *     action: 'used',
 *     usedBy: $user,
 *     changes: ['plan' => 'Pro', 'discount' => '50%']
 * );
 */
class PromoCodeActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'performed_by_user_id',
        'promo_code_id',
        'promo_code',
        'action',
        'changes',
        'used_by_user_id',
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
     * Betroffener Promo-Code
     */
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    /**
     * User der den Code verwendet hat
     */
    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    /**
     * Helper: Log erstellen
     */
    public static function log(
        ?User $performedBy,
        PromoCode $promoCode,
        string $action,
        ?array $changes = null,
        ?User $usedBy = null,
        ?string $description = null
    ): self {
        return self::create([
            'performed_by_user_id' => $performedBy?->id,
            'promo_code_id' => $promoCode->id,
            'promo_code' => $promoCode->code,
            'action' => $action,
            'changes' => $changes,
            'used_by_user_id' => $usedBy?->id,
            'ip_address' => request()->ip(),
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
