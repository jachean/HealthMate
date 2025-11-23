<script setup>
import { ref } from 'vue'

const props = defineProps({
  modelValue: String,
  label: {
    type: String,
    default: 'Password',
  },
  rules: {
    type: Array,
    default: () => [],
  },
  autocomplete: {
    type: String,
    default: 'current-password',
  },
  fieldClass: {
    type: [String, Array, Object],
    default: '',
  },
})

const emit = defineEmits(['update:modelValue'])

const visible = ref(false)
</script>

<template>
  <v-text-field
    :model-value="modelValue"
    :label="label"
    :type="visible ? 'text' : 'password'"
    variant="outlined"
    density="comfortable"
    :rules="rules"
    validate-on="submit"
    :autocomplete="autocomplete"
    :class="['field', fieldClass]"
    :append-inner-icon="visible ? 'mdi-eye-off' : 'mdi-eye'"
    @click:append-inner="visible = !visible"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>
