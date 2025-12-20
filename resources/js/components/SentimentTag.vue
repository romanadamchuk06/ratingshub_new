<script setup>
// Sentiment Tag Component
// Zeigt ein einzelnes Sentiment als farbiges Badge/Tag an
// Verwendet für Review-Kategorien (Service, Qualität, etc.)

const props = defineProps({
  sentiment: {
    type: Object,
    required: true,
    // Expected: { category, sentiment, confidence, excerpt }
  },
  showExcerpt: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'md', // 'sm', 'md', 'lg'
  }
})

// Kategorie-Namen (aus config/review_categories.php)
// 12 Kategorien für umfassende Review-Analyse
const categoryNames = {
  // Hauptkriterien
  service: 'Service',
  quality: 'Qualität',
  price: 'Preis-Leistung',
  friendliness: 'Freundlichkeit',
  // Weitere Kriterien
  speed: 'Schnelligkeit',
  communication: 'Kommunikation',
  reliability: 'Zuverlässigkeit',
  cleanliness: 'Sauberkeit',
  competence: 'Kompetenz',
  atmosphere: 'Atmosphäre',
  accessibility: 'Erreichbarkeit',
  recommendation: 'Weiterempfehlung',
}

// Sentiment Colors (Ampel-System: Grün/Gelb/Rot)
const sentimentColors = {
  positive: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border border-green-300 dark:border-green-700',
  neutral: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border border-yellow-300 dark:border-yellow-700',
  negative: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border border-red-300 dark:border-red-700',
}

// Sentiment Icons
const sentimentIcons = {
  positive: '👍',
  neutral: '➖',
  negative: '👎',
}

// Size classes
const sizeClasses = {
  sm: 'text-xs px-2 py-0.5',
  md: 'text-sm px-2.5 py-1',
  lg: 'text-base px-3 py-1.5',
}

// Get category display name
const categoryName = categoryNames[props.sentiment.category] || props.sentiment.category

// Get color class
const colorClass = sentimentColors[props.sentiment.sentiment] || sentimentColors.neutral

// Get icon
const icon = sentimentIcons[props.sentiment.sentiment] || '•'

// Get size class
const sizeClass = sizeClasses[props.size] || sizeClasses.md
</script>

<template>
  <div class="inline-flex flex-col">
    <!-- Tag Badge -->
    <span
      :class="[colorClass, sizeClass]"
      class="inline-flex items-center gap-1.5 font-medium rounded-full"
      :title="showExcerpt && sentiment.excerpt ? sentiment.excerpt : ''"
    >
      <span>{{ icon }}</span>
      <span>{{ categoryName }}</span>

      <!-- Optional: Konfidenz anzeigen -->
      <span
        v-if="sentiment.confidence && sentiment.confidence < 0.8"
        class="opacity-60 text-xs"
      >
        {{ Math.round(sentiment.confidence * 100) }}%
      </span>
    </span>

    <!-- Optional: Text-Ausschnitt -->
    <span
      v-if="showExcerpt && sentiment.excerpt"
      class="text-xs text-gray-600 dark:text-gray-400 mt-1 italic max-w-xs truncate"
    >
      "{{ sentiment.excerpt }}"
    </span>
  </div>
</template>
