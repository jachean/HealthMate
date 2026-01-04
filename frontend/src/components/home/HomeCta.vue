<script setup></script>

<template>
  <v-card
    elevation="6"
    rounded="xl"
    color="primary"
    class="pa-6 text-white"
  >
    <v-row align="center">
      <v-col cols="12" md="8">
        <h2 class="text-h6 font-weight-bold mb-1">
          Ready to book your appointment?
        </h2>
        <p class="text-body-2">
          Start browsing now.
        </p>
      </v-col>

      <v-col
        cols="12"
        md="4"
        class="d-flex justify-end"
      >
        <v-btn
          variant="outlined"
          color="white"
          class="mr-3"
        >
          Browse specialties
        </v-btn>

        <v-btn color="white" variant="flat">
          Create account
        </v-btn>
      </v-col>
    </v-row>
  </v-card>
</template>
