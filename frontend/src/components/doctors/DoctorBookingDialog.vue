<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useDisplay } from 'vuetify'
import { useI18n } from 'vue-i18n'
import api from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import TimeSlotButton from '@/components/doctors/TimeSlotButton.vue'
import ClinicMap from '@/components/ui/ClinicMap.vue'
import { isoToDateKey, dateKeyToLabel } from '@/utils/date'

const props = defineProps({
  modelValue: Boolean,
  doctor: { type: Object, required: true },
})

const emit = defineEmits(['update:modelValue'])

const router = useRouter()
const auth = useAuthStore()
const { t } = useI18n()
const { smAndDown } = useDisplay()
const isMobile = computed(() => smAndDown.value)

// State
const loading = ref(false)
const loadingSlots = ref(false)
const booking = ref(false)
const services = ref([])
const slots = ref([])
const selectedService = ref(null)
const selectedSpecialty = ref(null)
const selectedDay = ref(null)
const selectedSlot = ref(null)
const error = ref(null)
const showSuccess = ref(false)
const activeTab = ref('booking')
const reviews = ref([])
const loadingReviews = ref(false)

// Computed
const isOpen = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
})

const doctorName = computed(() =>
  props.doctor?.fullName || `${props.doctor?.firstName || ''} ${props.doctor?.lastName || ''}`.trim()
)

const doctorInitials = computed(() => {
  const name = doctorName.value
  const parts = name.split(' ').filter(Boolean)
  return parts.length >= 2
    ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    : name.slice(0, 2).toUpperCase()
})

const dayOptions = computed(() => {
  const keys = [...new Set(slots.value.map(s => isoToDateKey(s.startAt)))]
  return keys.sort()
})

const slotsForSelectedDay = computed(() => {
  if (!selectedDay.value) return []
  return slots.value.filter(s => isoToDateKey(s.startAt) === selectedDay.value)
})

const availableSpecialties = computed(() => props.doctor.specialties ?? [])

const filteredServices = computed(() => {
  if (!selectedSpecialty.value) return services.value
  return services.value.filter(s => {
    const sp = s.medicalService?.specialty
    return !sp || sp.slug === selectedSpecialty.value.slug
  })
})

const canConfirm = computed(() => selectedService.value && selectedSlot.value && !booking.value)

const errorMessage = computed(() => {
  if (!error.value) return null
  const messages = {
    AUTH_REQUIRED: t('booking.authRequired'),
    SLOT_UNAVAILABLE: t('booking.slotUnavailable'),
    ALREADY_BOOKED: t('booking.alreadyBooked'),
  }
  return messages[error.value] || t('booking.unknownError')
})

// Watchers
watch(() => props.modelValue, (open) => {
  if (open) {
    showSuccess.value = false
    activeTab.value = 'booking'
    loadServices()
    loadReviews()
  }
}, { immediate: true })

watch(selectedDay, () => {
  selectedSlot.value = null
})

watch(selectedSpecialty, () => {
  selectedService.value = null
  selectedDay.value = null
  selectedSlot.value = null
  slots.value = []
})

watch(selectedService, () => {
  selectedDay.value = null
  selectedSlot.value = null
  if (selectedService.value) {
    loadAvailability()
  }
})

// Methods
function specialtyName(specialty) {
  const slug = specialty?.slug
  if (slug) {
    const key = `specialty.${slug}`
    const translated = t(key)
    if (translated !== key) return translated
  }
  return specialty?.name || ''
}

function serviceName(service) {
  const slug = service.medicalService?.slug
  if (slug) {
    const key = `services.${slug}`
    const translated = t(key)
    if (translated !== key) return translated
  }
  return service.medicalService?.name || ''
}

async function loadServices() {
  loading.value = true
  error.value = null
  services.value = []
  selectedSpecialty.value = null
  selectedService.value = null
  slots.value = []
  selectedDay.value = null
  selectedSlot.value = null

  try {
    const { data } = await api.get(`/api/doctors/${props.doctor.id}/services`)
    services.value = Array.isArray(data) ? data : []
    if (availableSpecialties.value.length === 1) {
      selectedSpecialty.value = availableSpecialties.value[0]
    }
  } catch {
    error.value = 'LOAD_FAILED'
  } finally {
    loading.value = false
  }
}

async function loadReviews() {
  loadingReviews.value = true
  reviews.value = []
  try {
    const { data } = await api.get(`/api/doctors/${props.doctor.id}/reviews`)
    reviews.value = Array.isArray(data) ? data : []
  } catch {
    // non-critical, silently ignore
  } finally {
    loadingReviews.value = false
  }
}

function formatReviewDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

async function loadAvailability() {
  loadingSlots.value = true
  error.value = null
  slots.value = []
  selectedDay.value = null
  selectedSlot.value = null

  try {
    const { data } = await api.get(`/api/doctors/${props.doctor.id}/availability`)
    slots.value = Array.isArray(data) ? data : []
    selectedDay.value = dayOptions.value[0] ?? null
  } catch {
    error.value = 'LOAD_FAILED'
  } finally {
    loadingSlots.value = false
  }
}

function selectService(service) {
  selectedService.value = service
  error.value = null
}

function selectSlot(slot) {
  selectedSlot.value = slot
  error.value = null
}

async function confirmBooking() {
  if (!canConfirm.value) return

  if (!auth.token) {
    error.value = 'AUTH_REQUIRED'
    return
  }

  booking.value = true
  error.value = null

  try {
    await api.post('/api/appointments', {
      timeSlotId: selectedSlot.value.id,
      doctorServiceId: selectedService.value.id,
    })
    showSuccess.value = true
    setTimeout(() => {
      isOpen.value = false
    }, 2000)
  } catch (e) {
    error.value = e?.response?.data?.error?.code || 'UNKNOWN'
  } finally {
    booking.value = false
  }
}

function goToLogin() {
  isOpen.value = false
  router.push({ name: 'login', query: { redirect: '/doctors' } })
}

function formatPrice(price) {
  const num = parseFloat(price)
  return num % 1 === 0 ? num.toFixed(0) : num.toFixed(2)
}
</script>

<template>
  <v-dialog v-model="isOpen" max-width="900" :persistent="booking" :fullscreen="isMobile" content-class="booking-dialog-overlay">
    <v-card class="booking-dialog">
      <!-- Header -->
      <div class="dialog-header">
        <v-btn
          icon
          variant="text"
          size="small"
          class="close-btn"
          @click="isOpen = false"
          :disabled="booking"
        >
          <v-icon>mdi-close</v-icon>
        </v-btn>

        <h2 class="text-h6 font-weight-bold">{{ t('booking.title') }}</h2>
      </div>

      <v-tabs v-model="activeTab" color="primary" density="compact" class="dialog-tabs">
        <v-tab value="booking">
          <v-icon start size="16">mdi-calendar-check</v-icon>
          {{ t('booking.title') }}
        </v-tab>
        <v-tab value="reviews">
          <v-icon start size="16">mdi-star-outline</v-icon>
          {{ t('review.reviews') }}
          <v-chip v-if="reviews.length" size="x-small" class="ml-2" color="primary" variant="tonal">{{ reviews.length }}</v-chip>
        </v-tab>
      </v-tabs>

      <v-divider />

      <!-- Content -->
      <v-window v-model="activeTab" class="dialog-windows">
      <v-window-item value="booking">
      <div class="dialog-body">
        <!-- Left Column: Doctor Info & Booking -->
        <div class="booking-column">
          <!-- Doctor Info -->
          <div class="doctor-card">
            <v-avatar color="primary" size="56" class="doctor-avatar">
              <span class="text-h6">{{ doctorInitials }}</span>
            </v-avatar>
            <div class="doctor-details">
              <h3 class="text-subtitle-1 font-weight-bold">Dr. {{ doctorName }}</h3>
              <p v-if="doctor.clinic?.name" class="text-body-2 text-medium-emphasis">
                {{ doctor.clinic.name }}
              </p>
            </div>
          </div>

          <v-divider class="my-4" />

          <!-- Loading State -->
          <div v-if="loading" class="loading-state">
            <v-progress-circular indeterminate color="primary" />
            <p class="text-body-2 text-medium-emphasis mt-3">{{ t('booking.loadingServices') }}</p>
          </div>

          <!-- Success State -->
          <div v-else-if="showSuccess" class="success-state">
            <v-icon color="success" size="64">mdi-check-circle</v-icon>
            <h3 class="text-h6 mt-3">{{ t('booking.booked') }}</h3>
            <p class="text-body-2 text-medium-emphasis">
              {{ t('booking.bookedSubtitle') }}
            </p>
          </div>

          <!-- No Services Available -->
          <div v-else-if="services.length === 0" class="empty-state">
            <v-icon size="48" color="grey-lighten-1">mdi-medical-bag</v-icon>
            <p class="text-body-1 mt-3">{{ t('booking.noServices') }}</p>
            <p class="text-body-2 text-medium-emphasis">
              {{ t('booking.noServicesHint') }}
            </p>
          </div>

          <!-- Booking Flow -->
          <template v-else>
            <!-- Step 1: Select Specialty -->
            <div v-if="availableSpecialties.length > 0" class="booking-step">
              <div class="step-header">
                <span class="step-number">1</span>
                <span class="step-title">{{ t('booking.stepSpecialty') }}</span>
              </div>
              <div class="day-chips">
                <v-chip
                  v-for="sp in availableSpecialties"
                  :key="sp.slug"
                  :color="selectedSpecialty?.slug === sp.slug ? 'primary' : undefined"
                  :variant="selectedSpecialty?.slug === sp.slug ? 'flat' : 'outlined'"
                  @click="selectedSpecialty = sp"
                  class="day-chip"
                >
                  {{ specialtyName(sp) }}
                </v-chip>
              </div>
            </div>

            <!-- Step 2: Select Service -->
            <div v-if="availableSpecialties.length === 0 || selectedSpecialty" class="booking-step">
              <div class="step-header">
                <span class="step-number">{{ availableSpecialties.length > 0 ? 2 : 1 }}</span>
                <span class="step-title">{{ t('booking.step1') }}</span>
              </div>

              <div class="services-list">
                <button
                  v-for="service in filteredServices"
                  :key="service.id"
                  type="button"
                  class="service-btn"
                  :class="{ 'service-btn--selected': selectedService?.id === service.id }"
                  @click="selectService(service)"
                >
                  <div class="service-info">
                    <span class="service-name">{{ serviceName(service) }}</span>
                    <span class="service-duration">{{ service.durationMinutes }} {{ t('booking.min') }}</span>
                  </div>
                  <div class="service-price-row">
                    <span class="service-price">{{ formatPrice(service.price) }} RON</span>
                    <v-icon v-if="selectedService?.id === service.id" size="16" class="check-icon">mdi-check</v-icon>
                  </div>
                </button>
              </div>
            </div>

            <!-- Step 3/2: Select Day (only after service is selected) -->
            <template v-if="selectedService">
              <div v-if="loadingSlots" class="loading-state" style="padding: 24px;">
                <v-progress-circular indeterminate color="primary" size="32" />
                <p class="text-body-2 text-medium-emphasis mt-2">{{ t('booking.loadingAvailability') }}</p>
              </div>

              <template v-else-if="dayOptions.length > 0">
                <div class="booking-step">
                  <div class="step-header">
                    <span class="step-number">{{ availableSpecialties.length > 0 ? 3 : 2 }}</span>
                    <span class="step-title">{{ t('booking.step2') }}</span>
                  </div>

                  <div class="day-chips">
                    <v-chip
                      v-for="day in dayOptions"
                      :key="day"
                      :color="selectedDay === day ? 'primary' : undefined"
                      :variant="selectedDay === day ? 'flat' : 'outlined'"
                      @click="selectedDay = day"
                      class="day-chip"
                    >
                      {{ dateKeyToLabel(day) }}
                    </v-chip>
                  </div>
                </div>

                <!-- Step 4/3: Select Time -->
                <div class="booking-step">
                  <div class="step-header">
                    <span class="step-number">{{ availableSpecialties.length > 0 ? 4 : 3 }}</span>
                    <span class="step-title">{{ t('booking.step3') }}</span>
                  </div>

                  <div class="time-slots-grid">
                    <TimeSlotButton
                      v-for="slot in slotsForSelectedDay"
                      :key="slot.id"
                      :slot="slot"
                      :selected="selectedSlot?.id === slot.id"
                      @click="selectSlot(slot)"
                    />
                  </div>

                  <p v-if="slotsForSelectedDay.length === 0" class="text-body-2 text-medium-emphasis text-center py-4">
                    {{ t('booking.noSlotsDay') }}
                  </p>
                </div>
              </template>

              <div v-else class="empty-state" style="padding: 24px;">
                <v-icon size="48" color="grey-lighten-1">mdi-calendar-remove</v-icon>
                <p class="text-body-1 mt-3">{{ t('booking.noSlots') }}</p>
                <p class="text-body-2 text-medium-emphasis">{{ t('booking.noSlotsHint') }}</p>
              </div>
            </template>

            <!-- Error Alert -->
            <v-alert
              v-if="error"
              :type="error === 'AUTH_REQUIRED' ? 'warning' : 'error'"
              variant="tonal"
              class="mt-4"
              rounded="lg"
              density="compact"
            >
              {{ errorMessage }}
              <template v-if="error === 'AUTH_REQUIRED'" #append>
                <v-btn variant="flat" color="warning" size="small" @click="goToLogin">
                  {{ t('nav.login') }}
                </v-btn>
              </template>
            </v-alert>

            <!-- Confirm Button -->
            <v-btn
              color="primary"
              size="large"
              block
              class="mt-4"
              :disabled="!canConfirm"
              :loading="booking"
              @click="confirmBooking"
            >
              <v-icon start>mdi-calendar-check</v-icon>
              {{ t('booking.confirm') }}
              <span v-if="selectedService" class="ml-1">
                &mdash; {{ formatPrice(selectedService.price) }} RON
              </span>
            </v-btn>
          </template>
        </div>

        <!-- Right Column: Map -->
        <div class="map-column">
          <div class="map-header">
            <v-icon size="18" class="mr-2">mdi-hospital-building</v-icon>
            <span class="text-subtitle-2 font-weight-medium">{{ t('booking.clinicLocation') }}</span>
          </div>
          <ClinicMap
            :address="doctor.clinic?.address"
            :city="doctor.clinic?.city"
            :clinic-name="doctor.clinic?.name"
          />
        </div>
      </div>
      </v-window-item>
      <v-window-item value="reviews" class="reviews-window">
        <div class="reviews-body">
          <div v-if="loadingReviews" class="loading-state">
            <v-progress-circular indeterminate color="primary" />
          </div>
          <div v-else-if="reviews.length === 0" class="empty-state">
            <v-icon size="48" color="grey-lighten-1">mdi-star-off-outline</v-icon>
            <p class="text-body-1 mt-3">{{ t('review.noReviews') }}</p>
          </div>
          <div v-else class="reviews-list">
            <div v-for="review in reviews" :key="review.id" class="review-card">
              <div class="review-header">
                <div class="review-author-info">
                  <v-avatar size="36" color="primary" variant="tonal">
                    <span class="text-caption font-weight-bold">{{ review.authorName.charAt(0).toUpperCase() }}</span>
                  </v-avatar>
                  <div>
                    <div class="text-subtitle-2 font-weight-bold">{{ review.authorName }}</div>
                    <div class="text-caption text-medium-emphasis">{{ formatReviewDate(review.createdAt) }}</div>
                  </div>
                </div>
                <v-rating
                  :model-value="review.rating"
                  readonly
                  half-increments
                  density="compact"
                  size="small"
                  color="amber"
                  active-color="amber"
                />
              </div>
              <p v-if="review.comment" class="review-comment">{{ review.comment }}</p>
            </div>
          </div>
        </div>
      </v-window-item>
      </v-window>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.booking-dialog {
  border-radius: 20px;
  overflow: hidden;
}

.dialog-header {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px 24px;
}

.close-btn {
  position: absolute;
  top: 12px;
  right: 12px;
}

.dialog-body {
  display: grid;
  grid-template-columns: 1fr 1fr;
  height: 650px;
}

@media (max-width: 959px) {
  .dialog-body {
    grid-template-columns: 1fr;
    height: auto;
  }

  .map-column {
    order: -1;
    height: 200px;
    border-left: none;
    border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  }

  .booking-column {
    padding: 16px;
  }

  .booking-dialog {
    border-radius: 0;
  }

  .dialog-header {
    padding: 16px;
  }

  .time-slots-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }

  .day-chips {
    overflow-x: auto;
    flex-wrap: nowrap;
    padding-bottom: 4px;
    -webkit-overflow-scrolling: touch;
  }

  .day-chip {
    flex-shrink: 0;
  }
}

.booking-column {
  padding: 24px;
  overflow-y: auto;
  overflow-x: hidden;
}

.map-column {
  display: flex;
  flex-direction: column;
  background: rgba(var(--v-theme-on-surface), 0.02);
  border-left: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  height: 100%;
}

.map-header {
  display: flex;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.doctor-card {
  display: flex;
  align-items: center;
  gap: 16px;
}

.doctor-avatar {
  box-shadow: 0 2px 8px rgba(var(--v-theme-primary), 0.25);
}

.doctor-details {
  flex: 1;
  min-width: 0;
}

.loading-state,
.success-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  text-align: center;
}

.booking-step {
  margin-bottom: 20px;
}

.step-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.step-number {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  background: rgb(var(--v-theme-primary));
  color: white;
  border-radius: 50%;
  font-size: 0.75rem;
  font-weight: 600;
}

.step-title {
  font-weight: 600;
  font-size: 0.9rem;
}

/* Services list */
.services-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 200px;
  overflow-y: auto;
}

.service-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 12px 16px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 10px;
  background: transparent;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: left;
}

.service-btn:hover {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.04);
}

.service-btn--selected {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.1);
}

.service-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.service-name {
  font-weight: 600;
  font-size: 0.875rem;
}

.service-duration {
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.5);
}

.service-price-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.service-price {
  font-weight: 700;
  font-size: 0.875rem;
  color: rgb(var(--v-theme-primary));
}

.check-icon {
  color: rgb(var(--v-theme-primary));
}

.day-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.day-chip {
  font-weight: 500;
}

.time-slots-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}

.dialog-tabs {
  padding: 0 16px;
}

.dialog-windows {
  flex: 1;
  overflow: hidden;
}

.dialog-windows :deep(.v-window__container) {
  overflow: hidden;
}

.reviews-window {
  height: 650px;
  overflow: hidden;
}

.reviews-body {
  height: 100%;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 24px;
}

.reviews-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.review-card {
  padding: 16px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 12px;
  background: rgba(var(--v-theme-surface-variant), 0.3);
}

.review-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}

.review-author-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.review-comment {
  font-size: 0.875rem;
  color: rgba(var(--v-theme-on-surface), 0.8);
  line-height: 1.5;
  margin: 0;
}

@media (max-width: 959px) {
  .reviews-window {
    height: auto;
    min-height: 300px;
  }
}
</style>

<style>
/* Non-scoped: targets Vuetify's overlay wrapper, which is teleported to <body>
   and is not reachable from scoped styles or :deep() */
.booking-dialog-overlay {
  overflow: hidden !important;
  border-radius: 20px !important;
  background: transparent !important;
}
</style>
