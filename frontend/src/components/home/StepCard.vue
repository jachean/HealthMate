<script setup>
import '@/styles/ui/cards.css'

defineProps({
  n: { type: Number, required: true },
  title: { type: String, required: true },
  text: { type: String, required: true },
})
</script>

<template>
  <article class="step-card">
    <div class="step-card__badge">{{ n }}</div>
    <h3 class="step-card__title">{{ title }}</h3>
    <p class="step-card__text">{{ text }}</p>
  </article>
</template>
