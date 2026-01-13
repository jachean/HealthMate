<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

const specialties = [
  { label: 'Cardiology', slug: 'cardiology' },
  { label: 'Dermatology', slug: 'dermatology' },
  { label: 'Pediatrics', slug: 'pediatrics' },
  { label: 'Dentistry', slug: 'dentistry' },
  { label: 'Neurology', slug: 'neurology' },
  { label: 'General medicine', slug: 'general-medicine' },
]

function goToSpecialty(slug) {
  router.push({
    path: '/doctors',
    query: {
      specialty: slug,
    },
  })
}
</script>

<template>
  <v-card elevation="6" rounded="xl" class="pa-6">
    <div class="d-flex justify-space-between align-center mb-4">
      <h2 class="text-h6 font-weight-bold">
        Popular specialties
      </h2>
      <span class="text-body-2 text-medium-emphasis">
        A quick way to start your booking flow
      </span>
    </div>

    <v-row dense>
      <v-col
        v-for="spec in specialties"
        :key="spec.slug"
        cols="12"
        sm="6"
        md="4"
      >
        <v-card
          elevation="2"
          rounded="lg"
          class="pa-4 cursor-pointer"
          hover
          @click="goToSpecialty(spec.slug)"
        >
          <div class="font-weight-bold mb-1">
            {{ spec.label }}
          </div>
          <div class="text-body-2 text-medium-emphasis">
            Browse doctors →
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-card>
</template>
