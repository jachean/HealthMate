<script setup>
defineProps({
  modelValue: {
    type: Number,
    required: true,
  },
  length: {
    type: Number,
    required: true,
  },
  visibleCount: {
    type: Number,
    default: 5,
  },
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <v-pagination
    :model-value="modelValue"
    :length="length"
    :total-visible="visibleCount"
    @update:model-value="emit('update:modelValue', $event)"
    rounded
    active-color="primary"
    density="comfortable"
  />
</template>
