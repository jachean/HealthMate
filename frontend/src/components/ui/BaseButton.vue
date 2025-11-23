<script setup>
defineProps({
  block: Boolean,
  loading: Boolean,
  type: { type: String, default: 'button' }
})
</script>

<template>
  <v-btn
    :block="block"
    :loading="loading"
    :type="type"
    class="primary-btn"
  >
    <slot />
  </v-btn>
</template>
