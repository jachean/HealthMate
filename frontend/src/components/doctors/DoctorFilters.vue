<script setup>
const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  cities: Array,
  clinics: Array,
  specialties: Array,
})

const emit = defineEmits(['update:modelValue', 'reset'])

function update(key, value) {
  emit('update:modelValue', {
    ...props.modelValue,
    [key]: value,
  })
}
</script>

<template>
  <v-card variant="outlined" class="pa-4">
    <div class="text-subtitle-2 mb-4">
      Filter options
    </div>

    <v-row dense>
      <v-col cols="12" md="4">
        <v-select
          label="City"
          multiple
          chips
          clearable
          :items="cities"
          item-title="label"
          item-value="value"
          :model-value="modelValue.city"
          @update:model-value="update('city', $event)"
        />
      </v-col>

      <v-col cols="12" md="4">
        <v-select
          label="Clinic"
          multiple
          chips
          clearable
          :items="clinics"
          item-title="label"
          item-value="value"
          :model-value="modelValue.clinic"
          @update:model-value="update('clinic', $event)"
        />
      </v-col>

      <v-col cols="12" md="4">
        <v-select
          label="Specialty"
          multiple
          chips
          clearable
          :items="specialties"
          item-title="label"
          item-value="value"
          :model-value="modelValue.specialty"
          @update:model-value="update('specialty', $event)"
        />
      </v-col>
    </v-row>

    <v-row class="mt-3">
      <v-col cols="12" md="6">
        <v-checkbox
          label="Accepts insurance"
          :model-value="modelValue.acceptsInsurance"
          @update:model-value="update('acceptsInsurance', $event)"
        />
      </v-col>

      <v-col cols="12" md="6" class="text-right">
        <v-btn variant="text" color="primary" @click="$emit('reset')">
          Reset filters
        </v-btn>
      </v-col>
    </v-row>
  </v-card>
</template>
