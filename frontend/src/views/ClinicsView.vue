<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/lib/api'
import { uploadUrl } from '@/utils/url'

const { t } = useI18n()

const clinics = ref([])
const loading = ref(true)
const fetchError = ref(false)

onMounted(async () => {
  try {
    const { data } = await api.get('/api/clinics')
    clinics.value = data
  } catch {
    fetchError.value = true
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <v-container max-width="960" class="py-10 px-4">

    <!-- Header -->
    <div class="text-center mb-10">
      <div class="text-h4 font-weight-bold mb-2">{{ t('clinics.title') }}</div>
      <div class="text-body-1 text-medium-emphasis">{{ t('clinics.subtitle') }}</div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="d-flex justify-center py-20">
      <v-progress-circular indeterminate color="primary" size="40" />
    </div>

    <!-- Error -->
    <v-alert v-else-if="fetchError" type="error" variant="tonal" rounded="xl">
      {{ t('clinics.loadError') }}
    </v-alert>

    <!-- Empty -->
    <div v-else-if="clinics.length === 0" class="text-center py-20 text-medium-emphasis">
      <v-icon size="52" class="mb-4 opacity-30">mdi-hospital-building-outline</v-icon>
      <div class="text-body-1">{{ t('clinics.noClinics') }}</div>
    </div>

    <!-- Grid -->
    <div v-else class="clinics-grid">
      <v-card
        v-for="clinic in clinics"
        :key="clinic.id"
        :to="{ name: 'clinic-profile', params: { id: clinic.id } }"
        flat
        rounded="xl"
        class="clinic-card"
      >
        <!-- Top accent bar -->
        <div class="clinic-accent" />

        <div class="clinic-card-body pa-5">
          <!-- Icon + Name -->
          <div class="d-flex align-start ga-4 mb-4">
            <v-avatar :color="clinic.logoPath ? undefined : 'primary'" variant="tonal" size="48" rounded="lg" class="flex-shrink-0">
              <v-img v-if="clinic.logoPath" :src="uploadUrl(clinic.logoPath)" cover />
              <v-icon v-else size="24">mdi-hospital-building</v-icon>
            </v-avatar>
            <div class="flex-grow-1">
              <div class="text-subtitle-1 font-weight-bold">{{ clinic.name }}</div>
              <div class="d-flex align-center ga-1 mt-1">
                <v-icon size="13" color="medium-emphasis">mdi-map-marker-outline</v-icon>
                <span class="text-caption text-medium-emphasis">{{ clinic.city }}</span>
              </div>
            </div>
          </div>

          <!-- Address -->
          <div class="d-flex align-start ga-2 clinic-address">
            <v-icon size="15" color="primary" class="mt-0.5 flex-shrink-0">mdi-map-marker</v-icon>
            <span class="text-body-2 text-medium-emphasis">{{ clinic.address }}, {{ clinic.city }}</span>
          </div>

          <!-- CTA -->
          <v-btn
            color="primary"
            variant="tonal"
            rounded="lg"
            block
            size="small"
            append-icon="mdi-arrow-right"
          >
            {{ t('clinics.viewClinic') }}
          </v-btn>
        </div>
      </v-card>
    </div>

  </v-container>
</template>

<style scoped>
.clinics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.clinic-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.07);
  transition: border-color 0.2s ease, transform 0.18s ease, box-shadow 0.2s ease;
  overflow: hidden;
  text-decoration: none;
  cursor: pointer;
  display: flex !important;
  flex-direction: column;
}

.clinic-card:hover {
  border-color: rgba(var(--v-theme-primary), 0.3);
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(var(--v-theme-primary), 0.08) !important;
}

.clinic-accent {
  height: 4px;
  flex-shrink: 0;
  background: linear-gradient(90deg, rgb(var(--v-theme-primary)), rgba(var(--v-theme-primary), 0.4));
}

.clinic-card-body {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.clinic-address {
  flex: 1;
  margin-bottom: 16px;
}

.clinic-card-body .v-btn {
  flex: 0 0 auto !important;
}
</style>
