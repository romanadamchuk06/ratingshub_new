<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PLAN MODEL
 * ==========
 *
 * Repräsentiert einen Subscription-Plan (Free, Pro, Enterprise)
 *
 * STRUKTUR:
 * - Jeder Plan hat zwei Stripe Price IDs (monatlich + jährlich)
 * - Preise werden in Stripe verwaltet, hier nur für Anzeige
 * - Features als JSON-Array für flexible Darstellung
 *
 * WICHTIG:
 * - Stripe Price IDs im Admin-Panel konfigurieren
 * - max_platforms: 1000 = Unbegrenzt
 */
class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        // Stripe Price IDs (beide für monatlich/jährlich)
        'stripe_price_id_monthly',
        'stripe_price_id_yearly',
        // Preise (für Anzeige in der App)
        'price',
        'price_yearly',
        // Limits & Features
        'max_platforms',
        'description',
        'features',
        // Status
        'is_active',
        'is_popular',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'features' => 'array',
    ];

    /**
     * User die diesen Plan haben
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Prüft ob Plan kostenlos ist
     */
    public function isFree(): bool
    {
        return $this->price == 0;
    }

    /**
     * Prüft ob Plan Stripe Preise hat (bezahlter Plan)
     */
    public function hasStripePrice(): bool
    {
        return $this->stripe_price_id_monthly || $this->stripe_price_id_yearly;
    }

    /**
     * Findet Plan anhand Stripe Price ID
     *
     * Prüft sowohl monatliche als auch jährliche Price IDs.
     * Wird vom Webhook verwendet um den richtigen Plan zuzuweisen.
     */
    public static function findByStripePrice(string $stripePriceId): ?Plan
    {
        return static::where('is_active', true)
            ->where(function ($query) use ($stripePriceId) {
                $query->where('stripe_price_id_monthly', $stripePriceId)
                    ->orWhere('stripe_price_id_yearly', $stripePriceId);
            })
            ->first();
    }

    /**
     * Formatierter monatlicher Preis
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->price == 0) {
            return 'Kostenlos';
        }
        return number_format($this->price, 2, ',', '.') . ' €/Monat';
    }

    /**
     * Formatierter jährlicher Preis
     */
    public function getFormattedPriceYearlyAttribute(): string
    {
        if (!$this->price_yearly) {
            return '-';
        }
        return number_format($this->price_yearly, 2, ',', '.') . ' €/Jahr';
    }

    /**
     * Ersparnis bei jährlicher Zahlung in Prozent
     * Standard: ~17% (10 Monate statt 12)
     */
    public function getYearlySavingsPercentAttribute(): int
    {
        if (!$this->price || !$this->price_yearly || $this->price == 0) {
            return 0;
        }
        $monthlyTotal = $this->price * 12;
        $savings = (($monthlyTotal - $this->price_yearly) / $monthlyTotal) * 100;
        return (int) round($savings);
    }
}
