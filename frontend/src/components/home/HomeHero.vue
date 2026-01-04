<script setup></script>

<template>
  <v-card elevation="8" rounded="xl" class="pa-8">
    <v-row align="center">
      <v-col cols="12" md="6">
        <h1 class="text-h4 font-weight-bold mb-3">
          Book medical appointments, stress-free
        </h1>

        <p class="text-body-1 text-medium-emphasis mb-6">
          Browse specialties and clinics without an account.
          Log in only when confirming a booking.
        </p>

        <v-row class="mb-4" dense>
          <v-col cols="auto">
            <v-btn color="primary" size="large">
              Find a doctor
            </v-btn>
          </v-col>
          <v-col cols="auto">
            <v-btn variant="outlined" size="large">
              Create account
            </v-btn>
          </v-col>
        </v-row>

        <v-alert
          density="compact"
          type="success"
          variant="tonal"
          icon="mdi-lightbulb-outline"
        >
          Start by picking a specialty — we’ll show available clinics next.
        </v-alert>
      </v-col>

      <v-col cols="12" md="6" class="d-none d-md-flex">
        <v-card
          variant="tonal"
          color="primary"
          rounded="xl"
          class="pa-6 w-100"
        >
          <v-skeleton-loader type="heading" class="mb-3" />
          <v-skeleton-loader type="chip" class="mb-2" />
          <v-skeleton-loader type="card, card" />
        </v-card>
      </v-col>
    </v-row>
  </v-card>
</template>
