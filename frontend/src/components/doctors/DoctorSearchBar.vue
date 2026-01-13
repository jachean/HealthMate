<script setup>
defineProps({
  modelValue: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue', 'filters'])
</script>

<template>
  <v-row align="center" class="mb-4" no-gutters>
    <v-col cols="12" md="9">
      <v-text-field
        :model-value="modelValue"
        @update:model-value="emit('update:modelValue', $event)"
        label="Search doctors, clinics, specialties"
        prepend-inner-icon="mdi-magnify"
        variant="outlined"
        clearable
        hide-details
      />
    </v-col>

    <v-col cols="12" md="3" class="pl-md-3 mt-2 mt-md-0">
      <v-btn
        block
        variant="outlined"
        prepend-icon="mdi-filter-variant"
        @click="emit('filters')"
      >
        Filters
      </v-btn>
    </v-col>
  </v-row>
</template>
