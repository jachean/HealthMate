<script setup>
import { computed } from 'vue'

const props = defineProps({
  doctor: { type: Object, required: true },
})

const emit = defineEmits(['select'])

const specialtyChips = computed(() => {
  const arr = props.doctor?.specialties || []
  return arr.map((s) => {
    if (typeof s === 'string') {
      return { key: s, label: s }
    }
    // object shape
    return { key: s.slug || s.id || s.name, label: s.name || String(s.slug || '') }
  })
})

function onSelect() {
  emit('select', props.doctor)
}
</script>

<template>
  <v-card
    class="pa-4"
    elevation="2"
    rounded="lg"
    hover
    @click="onSelect"
  >
    <div class="d-flex align-center">
      <v-avatar color="primary" class="mr-4">
        {{ (doctor.fullName || `${doctor.firstName || ''} ${doctor.lastName || ''}`).trim().charAt(0) }}
      </v-avatar>

      <div class="flex-grow-1">
        <div class="font-weight-bold">
          {{ doctor.fullName || `${doctor.firstName || ''} ${doctor.lastName || ''}` }}
        </div>

        <div class="text-body-2 text-medium-emphasis">
          {{ doctor.clinic?.name || doctor.clinicName || '' }}
        </div>

        <div
          v-if="doctor.clinic?.city || doctor.city"
          class="text-body-2 text-medium-emphasis d-flex align-center"
        >
          <v-icon size="16" class="mr-1">mdi-map-marker-outline</v-icon>
          {{ doctor.clinic?.city || doctor.city }}
        </div>
      </div>
    </div>

    <div class="mt-3">
      <v-chip
        v-for="spec in specialtyChips"
        :key="spec.key"
        size="small"
        class="mr-2 mb-2"
        color="primary"
        variant="tonal"
      >
        {{ spec.label }}
      </v-chip>
    </div>

    <div class="mt-2 text-body-2 d-flex align-center">
      <v-icon
        size="16"
        :color="doctor.acceptsInsurance ? 'success' : 'error'"
        class="mr-1"
      >
        {{ doctor.acceptsInsurance ? 'mdi-check-circle' : 'mdi-close-circle' }}
      </v-icon>
      {{ doctor.acceptsInsurance ? 'Accepts insurance' : 'No insurance' }}
    </div>
  </v-card>
</template>
