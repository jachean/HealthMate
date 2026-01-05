<script setup>
const specialties = [
  'Cardiology',
  'Dermatology',
  'Pediatrics',
  'Dentistry',
  'Neurology',
  'General medicine',
]
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
        :key="spec"
        cols="12"
        sm="6"
        md="4"
      >
        <v-card
          elevation="2"
          rounded="lg"
          class="pa-4"
          hover
        >
          <div class="font-weight-bold mb-1">
            {{ spec }}
          </div>
          <div class="text-body-2 text-medium-emphasis">
            Browse doctors →
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-card>
</template>
