<script setup>
const steps = [
  {
    n: 1,
    title: 'Pick a specialty',
    text: 'Start broad — we’ll narrow down clinics and doctors next.',
  },
  {
    n: 2,
    title: 'Choose clinic & doctor',
    text: 'Compare options across associated clinics.',
  },
  {
    n: 3,
    title: 'Confirm time slot',
    text: 'Log in only when confirming your appointment.',
  },
]
</script>

<template>
  <v-card elevation="6" rounded="xl" class="pa-6">
    <h2 class="text-h6 font-weight-bold mb-1">
      How it works
    </h2>
    <p class="text-body-2 text-medium-emphasis mb-4">
      A simple booking flow designed for patients.
    </p>

    <v-row dense>
      <v-col
        v-for="step in steps"
        :key="step.n"
        cols="12"
        md="4"
      >
        <v-card elevation="3" rounded="lg" class="pa-4 h-100">
          <v-chip
            color="primary"
            class="mb-3"
            label
          >
            {{ step.n }}
          </v-chip>

          <div class="font-weight-bold mb-1">
            {{ step.title }}
          </div>

          <div class="text-body-2 text-medium-emphasis">
            {{ step.text }}
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-card>
</template>
