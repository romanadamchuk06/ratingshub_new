<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Services\AIResponseService;
use Illuminate\Http\Request;

/**
 * AI Response Controller
 *
 * Handhabt AI-generierte Antworten auf Reviews
 */
class AIResponseController extends Controller
{
    protected $aiService;

    public function __construct(AIResponseService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generiert eine AI-Antwort für ein Review
     *
     * POST /reviews/{review}/ai-response
     *
     * Body:
     * {
     *   "style": "friendly",  // professional, friendly, concise, enthusiastic, empathetic
     *   "context": {          // Optional
     *     "business_name": "Mein Restaurant",
     *     "location_name": "München"
     *   }
     * }
     */
    public function generate(Request $request, Review $review)
    {
        // Authorization: User darf nur eigene Reviews beantworten
        if ($review->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'style' => 'required|string|in:professional,friendly,concise,enthusiastic,empathetic',
            'context' => 'sometimes|array',
            'context.business_name' => 'sometimes|string|max:255',
            'context.location_name' => 'sometimes|string|max:255',
        ]);

        try {
            $style = $request->input('style', 'friendly');
            $context = $request->input('context', []);

            // Default Context aus Review-Daten
            if (!isset($context['location_name']) && $review->metadata && isset($review->metadata['location_name'])) {
                $context['location_name'] = $review->metadata['location_name'];
            }

            $generatedResponse = $this->aiService->generateResponse($review, $style, $context);

            return response()->json([
                'success' => true,
                'response' => $generatedResponse,
                'style' => $style,
                'review_id' => $review->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('AI Response generation failed', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler bei der AI-Generierung: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gibt alle verfügbaren Stile zurück
     *
     * GET /ai/styles
     */
    public function styles()
    {
        return response()->json([
            'styles' => AIResponseService::getAvailableStyles(),
        ]);
    }
}
