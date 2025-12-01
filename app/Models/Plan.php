<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'stripe_plan_id',
        'price',
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

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2) . ' €';
    }
}
