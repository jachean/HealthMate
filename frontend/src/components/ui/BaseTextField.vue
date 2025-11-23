<script setup>
const props = defineProps({
  modelValue: [String, Number],
  label: String,
  type: { type: String, default: 'text' },
  autocomplete: String,
  rules: { type: Array, default: () => [] },
  fieldClass: { type: [String, Array, Object], default: '' }
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <v-text-field
    :model-value="modelValue"
    :label="label"
    :type="type"
    variant="outlined"
    density="comfortable"
    :rules="rules"
    validate-on="submit"
    :autocomplete="autocomplete"
    :class="['field', fieldClass]"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>
