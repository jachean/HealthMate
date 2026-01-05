<script setup>
const items = [
  {
    icon: 'mdi-magnify',
    title: 'Browse without login',
    text: 'Search specialties and clinics freely. Log in only when confirming.',
  },
  {
    icon: 'mdi-hospital-building',
    title: 'Multiple clinics',
    text: 'Browse options across clinics and book the time that fits.',
  },
  {
    icon: 'mdi-check-circle-outline',
    title: 'Fast booking flow',
    text: 'Specialty → clinic → doctor → time slot → confirm.',
  },
]
</script>

<template>
  <v-row dense>
    <v-col
      v-for="item in items"
      :key="item.title"
      cols="12"
      md="4"
    >
      <v-card elevation="4" rounded="lg" class="pa-4 h-100">
        <v-icon color="primary" size="28" class="mb-2">
          {{ item.icon }}
        </v-icon>

        <div class="text-subtitle-1 font-weight-bold">
          {{ item.title }}
        </div>

        <div class="text-body-2 text-medium-emphasis">
          {{ item.text }}
        </div>
      </v-card>
    </v-col>
  </v-row>
</template>
