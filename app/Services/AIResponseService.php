<?php

namespace App\Services;

use App\Models\Review;
use Illuminate\Support\Facades\Log;
use OpenAI;

/**
 * AI Response Service
 *
 * Generiert automatische Antworten auf Reviews mit OpenAI GPT-4o-mini.
 * Kosten: ~$0.15 pro 1 Million Input-Tokens (sehr günstig)
 */
class AIResponseService
{
    private $client;

    /**
     * Verfügbare Antwort-Stile
     */
    const STYLES = [
        'professional' => [
            'name' => 'Professionell',
            'description' => 'Formell und geschäftsmäßig',
            'tone' => 'professional, formal, business-like',
        ],
        'friendly' => [
            'name' => 'Freundlich',
            'description' => 'Warm und persönlich',
            'tone' => 'friendly, warm, personal, approachable',
        ],
        'concise' => [
            'name' => 'Kurz & Knapp',
            'description' => 'Prägnant und auf den Punkt',
            'tone' => 'concise, brief, to-the-point',
        ],
        'enthusiastic' => [
            'name' => 'Enthusiastisch',
            'description' => 'Energiegeladen und positiv',
            'tone' => 'enthusiastic, energetic, positive, upbeat',
        ],
        'empathetic' => [
            'name' => 'Empathisch',
            'description' => 'Verständnisvoll und mitfühlend',
            'tone' => 'empathetic, understanding, compassionate',
        ],
    ];

    public function __construct()
    {
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            throw new \Exception('OpenAI API Key nicht konfiguriert. Bitte OPENAI_API_KEY in .env setzen.');
        }

        $this->client = OpenAI::client($apiKey);
    }

    /**
     * Generiert eine Antwort auf ein Review
     */
    public function generateResponse(Review $review, string $style = 'friendly', array $context = []): string
    {
        // Validiere Stil
        if (!isset(self::STYLES[$style])) {
            $style = 'friendly';
        }

        $styleConfig = self::STYLES[$style];
        $prompt = $this->buildPrompt($review, $styleConfig, $context);

        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $this->getSystemPrompt($styleConfig['tone'])],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 300,
            ]);

            return trim($response->choices[0]->message->content, '"\'');

        } catch (\Exception $e) {
            Log::error('AI Response Generation failed', [
                'review_id' => $review->id,
                'style' => $style,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Fehler bei AI-Generierung: ' . $e->getMessage());
        }
    }

    /**
     * System-Prompt für OpenAI
     */
    private function getSystemPrompt(string $tone): string
    {
        return "Du bist ein hilfreicher Assistent, der Antworten auf Kundenbewertungen schreibt.

Deine Aufgabe:
- Schreibe eine passende Antwort auf die Bewertung
- Ton: {$tone}
- Sprache: Deutsch
- Länge: 2-4 Sätze
- Sei authentisch und menschlich
- Bedanke dich bei positiven Reviews
- Zeige Verständnis bei negativen Reviews
- Biete Lösungen an wenn nötig
- Verwende KEINE Anführungszeichen um die Antwort
- Schreibe DIREKT die Antwort, ohne Präfix wie 'Hier ist eine Antwort:'";
    }

    /**
     * Baut den Prompt für die Review-Antwort
     */
    private function buildPrompt(Review $review, array $styleConfig, array $context): string
    {
        $businessName = $context['business_name'] ?? 'unser Unternehmen';
        $locationName = $context['location_name'] ?? '';

        $rating = $review->rating;
        $reviewText = $review->text ?? 'Keine Bewertungstext';
        $reviewerName = $review->reviewer_name ?? 'Kunde';

        $reviewType = $rating >= 4 ? 'positiv' : ($rating <= 2 ? 'negativ' : 'neutral');

        $prompt = "Schreibe eine {$styleConfig['tone']} Antwort auf folgende Bewertung:

Bewertung: {$rating}/5 Sterne ({$reviewType})
Von: {$reviewerName}
Text: \"{$reviewText}\"

Business: {$businessName}";

        if ($locationName) {
            $prompt .= "\nStandort: {$locationName}";
        }

        $prompt .= "\n\nSchreibe eine passende, natürliche Antwort die auf den Inhalt der Bewertung eingeht.";

        return $prompt;
    }

    /**
     * Gibt alle verfügbaren Stile zurück
     */
    public static function getAvailableStyles(): array
    {
        return self::STYLES;
    }
}
