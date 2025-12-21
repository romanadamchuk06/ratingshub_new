<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Business Profile Model
 *
 * Enthält alle Informationen über das Unternehmen des Users:
 * - Firmendaten (Name, Beschreibung, Branche)
 * - Kontaktdaten (Telefon, E-Mail, Website)
 * - Adresse
 * - Öffnungszeiten
 * - Logo
 * - Social Media Links
 *
 * Beziehung: 1:1 mit User (jeder User hat EIN Business Profile)
 */
class BusinessProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'description',
        'industry',
        'phone',
        'email',
        'website',
        'street',
        'city',
        'postal_code',
        'country',
        'opening_hours',
        'logo_url',
        'social_links',
        'metadata',
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'social_links' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Business Profile gehört zu einem User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Gibt formatierte Öffnungszeiten für einen bestimmten Wochentag zurück
     */
    public function getOpeningHoursForDay(string $day): ?array
    {
        if (!$this->opening_hours || !isset($this->opening_hours[$day])) {
            return null;
        }

        return $this->opening_hours[$day];
    }

    /**
     * Prüft ob das Geschäft an einem bestimmten Tag geöffnet ist
     */
    public function isOpenOn(string $day): bool
    {
        $hours = $this->getOpeningHoursForDay($day);

        if (!$hours) {
            return false;
        }

        return !($hours['closed'] ?? false);
    }

    /**
     * Gibt vollständige Adresse als String zurück
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street,
            $this->postal_code . ' ' . $this->city,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Default Öffnungszeiten (Mo-Fr 9-18 Uhr, Sa/So geschlossen)
     */
    public static function getDefaultOpeningHours(): array
    {
        return [
            'monday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'tuesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'wednesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'thursday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'friday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'saturday' => ['open' => '10:00', 'close' => '14:00', 'closed' => true],
            'sunday' => ['open' => '00:00', 'close' => '00:00', 'closed' => true],
        ];
    }
}
