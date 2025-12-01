<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
        'description',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function canBeUsedBy(User $user): bool
    {
        // Admins können alle Promo-Codes ohne Einschränkungen nutzen
        if ($user->is_admin) {
            return $this->is_active; // Nur prüfen, ob aktiv
        }

        if (!$this->isValid()) {
            return false;
        }

        // Check if user already used this code
        return !$this->usages()->where('user_id', $user->id)->exists();
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->type === 'percentage') {
            return $amount * ($this->value / 100);
        }

        // Fixed amount
        return min($this->value, $amount); // Don't discount more than the amount
    }

    public function applyDiscount(float $amount): float
    {
        return $amount - $this->calculateDiscount($amount);
    }

    public function markAsUsed(User $user): void
    {
        // Admin-Verwendungen nicht zählen
        if (!$user->is_admin) {
            $this->increment('used_count');
        }

        $this->usages()->create([
            'user_id' => $user->id,
            'used_at' => now(),
        ]);
    }
}
