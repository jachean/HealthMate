<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '@/lib/api'
import ClinicMap from '@/components/ui/ClinicMap.vue'
import { uploadUrl } from '@/utils/url'
import DoctorBookingDialog from '@/components/doctors/DoctorBookingDialog.vue'

const route = useRoute()
const { t } = useI18n()

const clinic = ref(null)
const doctors = ref([])
const loading = ref(true)
const notFound = ref(false)

const bookingDoctor = ref(null)
const bookingOpen = ref(false)

onMounted(async () => {
  try {
    const [clinicRes, doctorsRes] = await Promise.all([
      api.get(`/api/clinics/${route.params.id}`),
      api.get(`/api/clinics/${route.params.id}/doctors`),
    ])
    clinic.value = clinicRes.data
    doctors.value = Array.isArray(doctorsRes.data) ? doctorsRes.data : []
  } catch (e) {
    if (e?.response?.status === 404) notFound.value = true
  } finally {
    loading.value = false
  }
})

function doctorInitials(doctor) {
  return (doctor.firstName?.charAt(0) ?? '') + (doctor.lastName?.charAt(0) ?? '')
}

function doctorName(doctor) {
  return `${doctor.firstName ?? ''} ${doctor.lastName ?? ''}`.trim()
}

function minPrice(doctor) {
  const prices = (doctor.doctorServices ?? []).map(s => parseFloat(s.price)).filter(p => !isNaN(p))
  return prices.length ? Math.min(...prices) : null
}

function specialtyName(sp) {
  const key = `specialty.${sp.slug}`
  const translated = t(key)
  return translated !== key ? translated : sp.name
}

function openBooking(doctor) {
  bookingDoctor.value = doctor
  bookingOpen.value = true
}

const activeDoctors = computed(() => doctors.value.filter(d => d.isActive !== false))
</script>

<template>
  <!-- Loading -->
  <div v-if="loading" class="d-flex justify-center align-center" style="min-height: 60vh;">
    <v-progress-circular indeterminate color="primary" size="48" />
  </div>

  <!-- Not found -->
  <v-container v-else-if="notFound" max-width="560" class="py-20 text-center">
    <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-hospital-building-outline</v-icon>
    <div class="text-h6 font-weight-bold mb-2">{{ t('clinicProfile.notFound') }}</div>
    <div class="text-body-2 text-medium-emphasis mb-6">{{ t('clinicProfile.notFoundHint') }}</div>
    <v-btn color="primary" variant="tonal" :to="{ name: 'clinics' }">
      <v-icon start>mdi-arrow-left</v-icon>
      {{ t('clinics.title') }}
    </v-btn>
  </v-container>

  <!-- Content -->
  <template v-else-if="clinic">

    <!-- Hero -->
    <div class="clinic-hero">
      <v-container max-width="1100" class="py-10 px-4">
        <div class="d-flex align-start ga-5 flex-wrap">
          <v-avatar :color="clinic.logoPath ? undefined : 'primary'" variant="tonal" size="72" rounded="xl" class="flex-shrink-0">
            <v-img v-if="clinic.logoPath" :src="uploadUrl(clinic.logoPath)" cover />
            <v-icon v-else size="36">mdi-hospital-building</v-icon>
          </v-avatar>
          <div class="flex-grow-1">
            <div class="text-h4 font-weight-bold mb-1">{{ clinic.name }}</div>
            <div class="d-flex align-center ga-2 flex-wrap">
              <v-icon size="16" color="medium-emphasis">mdi-map-marker-outline</v-icon>
              <span class="text-body-2 text-medium-emphasis">{{ clinic.address }}, {{ clinic.city }}</span>
            </div>
            <div v-if="activeDoctors.length" class="d-flex align-center ga-2 mt-2">
              <v-icon size="16" color="primary">mdi-doctor</v-icon>
              <span class="text-body-2">
                <strong>{{ activeDoctors.length }}</strong>
                {{ t('clinicProfile.doctorCount', activeDoctors.length) }}
              </span>
            </div>
          </div>
        </div>
      </v-container>
    </div>

    <v-divider />

    <!-- Body -->
    <v-container max-width="1100" class="py-8 px-4">
      <div class="clinic-layout">

        <!-- Left column -->
        <div class="clinic-main">

          <!-- About -->
          <section v-if="clinic.description" class="mb-8">
            <div class="section-title mb-3">
              <v-icon size="18" color="primary" class="mr-2">mdi-information-outline</v-icon>
              {{ t('clinicProfile.about') }}
            </div>
            <p class="text-body-2" style="line-height: 1.7;">{{ clinic.description }}</p>
          </section>

          <!-- Doctors -->
          <section>
            <div class="section-title mb-4">
              <v-icon size="18" color="primary" class="mr-2">mdi-doctor</v-icon>
              {{ t('clinicProfile.doctors') }}
              <v-chip v-if="activeDoctors.length" size="x-small" color="primary" variant="tonal" class="ml-2">
                {{ activeDoctors.length }}
              </v-chip>
            </div>

            <div v-if="activeDoctors.length === 0" class="text-body-2 text-medium-emphasis py-4">
              {{ t('clinicProfile.noDoctors') }}
            </div>

            <div v-else class="doctors-list">
              <v-card
                v-for="doctor in activeDoctors"
                :key="doctor.id"
                flat
                rounded="xl"
                class="doctor-card"
              >
                <div class="d-flex align-start ga-4 pa-4">
                  <!-- Avatar -->
                  <v-avatar :color="doctor.avatarPath ? undefined : 'primary'" size="48" class="flex-shrink-0">
                    <v-img v-if="doctor.avatarPath" :src="uploadUrl(doctor.avatarPath)" cover />
                    <span v-else class="text-subtitle-2 font-weight-bold text-white">{{ doctorInitials(doctor) }}</span>
                  </v-avatar>

                  <!-- Info -->
                  <div class="flex-grow-1 overflow-hidden">
                    <div class="d-flex align-center ga-2 flex-wrap mb-1">
                      <span class="text-subtitle-2 font-weight-bold">Dr. {{ doctorName(doctor) }}</span>
                      <v-chip
                        v-if="doctor.acceptsInsurance"
                        size="x-small"
                        color="success"
                        variant="tonal"
                      >
                        <v-icon start size="10">mdi-shield-check</v-icon>
                        {{ t('doctors.insurance') }}
                      </v-chip>
                    </div>

                    <!-- Specialties -->
                    <div class="d-flex flex-wrap ga-1 mb-2">
                      <v-chip
                        v-for="sp in doctor.specialties"
                        :key="sp.slug"
                        size="x-small"
                        color="primary"
                        variant="tonal"
                      >
                        {{ specialtyName(sp) }}
                      </v-chip>
                    </div>

                    <!-- Price -->
                    <div v-if="minPrice(doctor) !== null" class="text-caption text-medium-emphasis">
                      {{ t('doctors.from') }}
                      <strong class="text-body-2 text-on-surface">{{ minPrice(doctor).toFixed(0) }} RON</strong>
                    </div>
                  </div>

                  <!-- Book button -->
                  <div class="flex-shrink-0 d-flex flex-column align-end ga-2">
                    <v-btn
                      :to="{ name: 'doctor-profile', params: { id: doctor.id } }"
                      variant="text"
                      size="x-small"
                      color="primary"
                      density="compact"
                    >
                      {{ t('doctorProfile.viewProfile') }}
                    </v-btn>
                    <v-btn
                      color="primary"
                      size="small"
                      rounded="lg"
                      @click="openBooking(doctor)"
                    >
                      <v-icon start size="14">mdi-calendar-plus</v-icon>
                      {{ t('clinicProfile.book') }}
                    </v-btn>
                  </div>
                </div>
              </v-card>
            </div>
          </section>
        </div>

        <!-- Right column — map (sticky) -->
        <div class="clinic-sidebar">
          <div class="sidebar-sticky">
            <div class="section-title mb-3">
              <v-icon size="18" color="primary" class="mr-2">mdi-map-marker</v-icon>
              {{ t('clinicProfile.location') }}
            </div>
            <v-card flat rounded="xl" class="map-card" style="overflow: hidden;">
              <ClinicMap
                :address="clinic.address"
                :city="clinic.city"
                :clinic-name="clinic.name"
                style="height: 460px;"
              />
            </v-card>
            <div class="mt-3 d-flex align-start ga-2">
              <v-icon size="15" color="primary" class="mt-0.5">mdi-map-marker</v-icon>
              <span class="text-body-2 text-medium-emphasis">{{ clinic.address }}, {{ clinic.city }}</span>
            </div>
          </div>
        </div>

      </div>
    </v-container>

  </template>

  <!-- Booking dialog -->
  <DoctorBookingDialog
    v-if="bookingDoctor"
    v-model="bookingOpen"
    :doctor="bookingDoctor"
    @update:model-value="val => { if (!val) bookingDoctor = null }"
  />
</template>

<style scoped>
.clinic-hero {
  background: linear-gradient(
    135deg,
    rgba(var(--v-theme-primary), 0.05) 0%,
    rgba(var(--v-theme-surface), 1) 70%
  );
}

.clinic-layout {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 32px;
  align-items: start;
}

@media (max-width: 959px) {
  .clinic-layout {
    grid-template-columns: 1fr;
  }

  .clinic-sidebar {
    order: -1;
  }
}

.sidebar-sticky {
  position: sticky;
  top: 80px;
}

.section-title {
  display: flex;
  align-items: center;
  font-size: 0.95rem;
  font-weight: 700;
}

.map-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.doctors-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.doctor-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.07);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.doctor-card:hover {
  border-color: rgba(var(--v-theme-primary), 0.25);
  box-shadow: 0 4px 16px rgba(var(--v-theme-primary), 0.06) !important;
}
</style>
