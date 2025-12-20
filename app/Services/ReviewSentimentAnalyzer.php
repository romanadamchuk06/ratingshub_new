<?php

namespace App\Services;

use App\Models\Review;
use App\Models\ReviewSentiment;
use OpenAI;
use Illuminate\Support\Facades\Http;

/**
 * Review Sentiment Analyzer
 *
 * Analysiert Reviews automatisch mit AI und extrahiert Sentiments für verschiedene Kategorien
 * Nutzt OpenAI oder Ollama, abhängig von der Konfiguration
 */
class ReviewSentimentAnalyzer
{
    private $provider;
    private $client; // OpenAI client

    public function __construct()
    {
        // Bestimme Provider aus .env (default: openai)
        $this->provider = config('services.ai.provider', 'openai');

        // OpenAI Client initialisieren (falls OpenAI verwendet wird)
        if ($this->provider === 'openai') {
            $apiKey = config('services.openai.api_key');

            if (!$apiKey) {
                throw new \Exception('OpenAI API Key nicht konfiguriert. Bitte OPENAI_API_KEY in .env setzen.');
            }

            $this->client = OpenAI::client($apiKey);
        }
    }

    /**
     * Analysiert ein Review und speichert die Sentiments
     *
     * @param Review $review Das zu analysierende Review
     * @return array Die erkannten Sentiments
     */
    public function analyze(Review $review): array
    {
        // Kein Text vorhanden? Abbruch
        if (empty($review->text)) {
            return [];
        }

        // Hole Kategorien aus Config
        $categories = config('review_categories.categories');

        // Baue Prompt für AI
        $prompt = $this->buildAnalysisPrompt($review, $categories);

        try {
            // AI-Analyse durchführen
            if ($this->provider === 'openai') {
                $result = $this->analyzeWithOpenAI($prompt);
            } else {
                $result = $this->analyzeWithOllama($prompt);
            }

            // Sentiments speichern
            $sentiments = $this->parseSentimentsFromResponse($result, $review);

            return $sentiments;
        } catch (\Exception $e) {
            \Log::error('Sentiment Analysis failed', [
                'provider' => $this->provider,
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Fehler bei der Sentiment-Analyse: ' . $e->getMessage());
        }
    }

    /**
     * Baut den Prompt für die Sentiment-Analyse
     */
    private function buildAnalysisPrompt(Review $review, array $categories): string
    {
        $categoryList = collect($categories)->map(function ($category, $key) {
            return "- {$key}: {$category['name']} - {$category['description']}";
        })->implode("\n");

        $prompt = "Analysiere folgende Bewertung und identifiziere, welche Kategorien erwähnt werden und ob das Sentiment positiv, neutral oder negativ ist.

Bewertung: {$review->rating}/5 Sterne
Text: \"{$review->text}\"

Kategorien:
{$categoryList}

Analysiere für JEDE erwähnte Kategorie:
1. Wird die Kategorie im Text erwähnt (direkt oder indirekt)?
2. Wenn ja: Ist das Sentiment positiv, neutral oder negativ?
3. Gib eine Konfidenz (0.0-1.0) an, wie sicher du bist
4. Optional: Zitiere den relevanten Textausschnitt

Gib das Ergebnis als JSON zurück in folgendem Format:
{
  \"sentiments\": [
    {
      \"category\": \"service\",
      \"sentiment\": \"positive\",
      \"confidence\": 0.95,
      \"excerpt\": \"Der Service war ausgezeichnet\"
    }
  ]
}

WICHTIG:
- Antworte NUR mit JSON, kein zusätzlicher Text
- Gib nur Kategorien zurück, die wirklich im Text erwähnt werden
- sentiment muss einer von: positive, neutral, negative sein
- confidence muss zwischen 0.0 und 1.0 liegen";

        return $prompt;
    }

    /**
     * Analysiert mit OpenAI
     */
    private function analyzeWithOpenAI(string $prompt): string
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Du bist ein Experte für Sentiment-Analyse von Kundenbewertungen. Antworte NUR mit gültigem JSON.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.3, // Niedrig für konsistente Ergebnisse
            'max_tokens' => 1000,
        ]);

        return $response->choices[0]->message->content;
    }

    /**
     * Analysiert mit Ollama (lokal)
     */
    private function analyzeWithOllama(string $prompt): string
    {
        $ollamaHost = config('services.ollama.host', 'http://ollama:11434');
        $ollamaModel = config('services.ollama.model', 'llama3.2');

        $systemPrompt = 'Du bist ein Experte für Sentiment-Analyse von Kundenbewertungen. Antworte NUR mit gültigem JSON.';
        $fullPrompt = $systemPrompt . "\n\n" . $prompt;

        // Ollama API Call
        $response = Http::timeout(180)->post("{$ollamaHost}/api/generate", [
            'model' => $ollamaModel,
            'prompt' => $fullPrompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.3,
                'num_predict' => 1000,
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Ollama API Fehler: ' . $response->body());
        }

        $data = $response->json();
        return $data['response'] ?? '';
    }

    /**
     * Parst die AI-Antwort und speichert Sentiments in der DB
     */
    private function parseSentimentsFromResponse(string $response, Review $review): array
    {
        // Cleanup: Entferne Markdown Code Blocks falls vorhanden
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        $response = trim($response);

        // Parse JSON
        $data = json_decode($response, true);

        if (!$data || !isset($data['sentiments']) || !is_array($data['sentiments'])) {
            \Log::warning('Invalid AI response for sentiment analysis', [
                'response' => $response,
                'review_id' => $review->id,
            ]);
            return [];
        }

        $savedSentiments = [];

        // Alte Sentiments löschen (für Re-Analyse)
        $review->sentiments()->delete();

        // Neue Sentiments speichern
        foreach ($data['sentiments'] as $sentimentData) {
            // Validierung
            if (!isset($sentimentData['category']) || !isset($sentimentData['sentiment'])) {
                continue;
            }

            // Sentiment muss gültig sein
            if (!in_array($sentimentData['sentiment'], ['positive', 'neutral', 'negative'])) {
                continue;
            }

            // Kategorie muss existieren
            $categories = config('review_categories.categories');
            if (!isset($categories[$sentimentData['category']])) {
                continue;
            }

            // Sentiment speichern
            $sentiment = ReviewSentiment::create([
                'review_id' => $review->id,
                'category' => $sentimentData['category'],
                'sentiment' => $sentimentData['sentiment'],
                'confidence' => $sentimentData['confidence'] ?? 0.8,
                'excerpt' => $sentimentData['excerpt'] ?? null,
            ]);

            $savedSentiments[] = $sentiment;
        }

        \Log::info('Sentiment Analysis completed', [
            'review_id' => $review->id,
            'sentiments_count' => count($savedSentiments),
        ]);

        return $savedSentiments;
    }

    /**
     * Re-Analysiere alle Reviews ohne Sentiments
     */
    public function analyzeAllPending(): int
    {
        $reviews = Review::whereDoesntHave('sentiments')
            ->whereNotNull('text')
            ->get();

        $analyzed = 0;

        foreach ($reviews as $review) {
            try {
                $this->analyze($review);
                $analyzed++;
            } catch (\Exception $e) {
                \Log::error('Failed to analyze review', [
                    'review_id' => $review->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $analyzed;
    }
}
