<script setup>
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const { t } = useI18n()

function goToDoctors() {
  router.push({ path: '/doctors' })
}

function goToRegister() {
  router.push({ name: 'register' })
}
</script>

<template>
  <div class="text-center text-white">
    <v-icon size="48" color="white" class="mb-4" style="opacity: 0.9;">
      mdi-calendar-heart
    </v-icon>

    <h2 class="cta-heading text-h4 font-weight-bold mb-3">
      {{ t('cta.title') }}
    </h2>
    <p class="text-body-1 mb-8 cta-subtitle">
      {{ t('cta.subtitle') }}
    </p>

    <div class="cta-actions d-flex justify-center ga-3 flex-wrap">
      <v-btn
        size="large"
        color="white"
        variant="flat"
        class="cta-btn text-primary font-weight-bold"
        @click="goToDoctors"
      >
        <v-icon start>mdi-magnify</v-icon>
        {{ t('cta.browseDoctors') }}
      </v-btn>

      <v-btn
        size="large"
        variant="outlined"
        color="white"
        class="cta-btn"
        @click="goToRegister"
      >
        {{ t('cta.createFreeAccount') }}
      </v-btn>
    </div>
  </div>
</template>

<style scoped>
.cta-subtitle {
  opacity: 0.85;
  max-width: 520px;
  margin-left: auto;
  margin-right: auto;
}

@media (max-width: 959px) {
  .cta-heading {
    font-size: 1.5rem !important;
  }
}

@media (max-width: 599px) {
  .cta-heading {
    font-size: 1.3rem !important;
  }

  .cta-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .cta-btn {
    width: 100%;
  }
}
</style>
