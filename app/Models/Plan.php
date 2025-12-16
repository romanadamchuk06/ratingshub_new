<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'stripe_plan_id',
        'price',
        'billing_interval', // 'monthly' oder 'yearly'
        'max_platforms',
        'is_active',
        'is_popular', // Zeigt "Beliebt"-Badge auf Pricing-Seite
        'sort_order',
        'description',
        'features',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_popular' => 'boolean', // Cast für Popular-Badge
        'features' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isFree(): bool
    {
        return $this->price == 0;
    }

    public function isYearly(): bool
    {
        return $this->billing_interval === 'yearly';
    }

    public function isMonthly(): bool
    {
        return $this->billing_interval === 'monthly';
    }

    public function getFormattedPriceAttribute(): string
    {
        $interval = $this->isYearly() ? '/Jahr' : '/Monat';
        return number_format($this->price, 2) . ' €' . $interval;
    }

    /**
     * Berechnet den monatlichen Preis für Vergleich
     * Beispiel: Jährlich 119.99€ → monatlich 10€
     */
    public function getMonthlyEquivalentAttribute(): float
    {
        if ($this->isYearly()) {
            return round($this->price / 12, 2);
        }
        return (float) $this->price;
    }
}
