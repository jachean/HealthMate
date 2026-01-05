<script setup>
import '@/styles/ui/cards.css'

defineProps({
  title: { type: String, required: true },
  text: { type: String, required: true },
  icon: { type: String, required: true },
})
</script>

<template>
  <article class="info-card">
    <div class="info-card__icon" aria-hidden="true">
      {{ icon }}
    </div>

    <div>
      <h3 class="info-card__title">{{ title }}</h3>
      <p class="info-card__text">{{ text }}</p>
    </div>
  </article>
</template>
