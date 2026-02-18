<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const { t } = useI18n()

const specialties = computed(() => [
  { label: t('specialties.cardiology'), slug: 'cardiology', icon: 'mdi-heart-pulse', color: '#E53935' },
  { label: t('specialties.dermatology'), slug: 'dermatology', icon: 'mdi-hand-heart-outline', color: '#8E24AA' },
  { label: t('specialties.pediatrics'), slug: 'pediatrics', icon: 'mdi-baby-face-outline', color: '#43A047' },
  { label: t('specialties.dentistry'), slug: 'dentistry', icon: 'mdi-tooth-outline', color: '#1E88E5' },
  { label: t('specialties.neurology'), slug: 'neurology', icon: 'mdi-brain', color: '#F4511E' },
  { label: t('specialties.generalMedicine'), slug: 'general-medicine', icon: 'mdi-stethoscope', color: '#00897B' },
])

function goToSpecialty(slug) {
  router.push({
    path: '/doctors',
    query: { specialty: slug },
  })
}
</script>

<template>
  <div>
    <div class="section-header mb-6">
      <h2 class="text-h5 font-weight-bold">{{ t('specialties.title') }}</h2>
      <p class="text-body-2 text-medium-emphasis mt-1">
        {{ t('specialties.subtitle') }}
      </p>
    </div>

    <v-row>
      <v-col
        v-for="spec in specialties"
        :key="spec.slug"
        cols="6"
        sm="4"
      >
        <v-card
          variant="outlined"
          class="specialty-card pa-5 h-100 cursor-pointer"
          hover
          @click="goToSpecialty(spec.slug)"
        >
          <div
            class="specialty-icon-wrapper mb-3"
            :style="{ background: spec.color + '14' }"
          >
            <v-icon :color="spec.color" size="28">{{ spec.icon }}</v-icon>
          </div>
          <div class="text-subtitle-2 font-weight-bold">{{ spec.label }}</div>
          <div class="text-caption text-medium-emphasis mt-1 d-flex align-center">
            {{ t('specialties.browseDoctors') }}
            <v-icon size="14" class="ml-1">mdi-arrow-right</v-icon>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<style scoped>
.specialty-card {
  border-color: rgba(var(--v-theme-on-surface), 0.08);
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.specialty-card:hover {
  border-color: rgba(var(--v-theme-primary), 0.3);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  transform: translateY(-2px);
}

.specialty-icon-wrapper {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  border-radius: 14px;
}

@media (max-width: 959px) {
  .section-header {
    text-align: center;
  }
}

@media (max-width: 599px) {
  .specialty-card {
    padding: 16px !important;
  }

  .specialty-icon-wrapper {
    width: 44px;
    height: 44px;
    border-radius: 12px;
  }
}
</style>
