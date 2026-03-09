<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import { uploadUrl } from '@/utils/url'
import TimeSlotButton from '@/components/doctors/TimeSlotButton.vue'
import ClinicMap from '@/components/ui/ClinicMap.vue'
import { isoToDateKey, dateKeyToLabel } from '@/utils/date'

const route  = useRoute()
const router = useRouter()
const auth   = useAuthStore()
const { t }  = useI18n()

const doctorId = computed(() => Number(route.params.id))

// ── Data ───────────────────────────────────────────────────────────────────
const doctor         = ref(null)
const loadingDoctor  = ref(true)
const notFound       = ref(false)
const services       = ref([])
const loadingServices = ref(false)
const slots          = ref([])
const loadingSlots   = ref(false)
const reviews        = ref([])
const loadingReviews = ref(false)

// ── Booking state ──────────────────────────────────────────────────────────
const selectedSpecialty = ref(null)
const selectedService   = ref(null)
const selectedDay       = ref(null)
const selectedSlot      = ref(null)
const booking           = ref(false)
const bookingError      = ref(null)
const showSuccess       = ref(false)

// ── Computed ───────────────────────────────────────────────────────────────
const doctorName = computed(() =>
  doctor.value ? `${doctor.value.firstName} ${doctor.value.lastName}` : ''
)

const doctorInitials = computed(() => {
  if (!doctor.value) return ''
  return (doctor.value.firstName[0] + doctor.value.lastName[0]).toUpperCase()
})

const averageRating = computed(() => {
  if (!reviews.value.length) return null
  const sum = reviews.value.reduce((acc, r) => acc + r.rating, 0)
  return Math.round((sum / reviews.value.length) * 10) / 10
})

const startingPrice = computed(() => {
  if (!services.value.length) return null
  return Math.min(...services.value.map(s => parseFloat(s.price)))
})

const availableSpecialties = computed(() => doctor.value?.specialties ?? [])

const dayOptions = computed(() => {
  const keys = [...new Set(slots.value.map(s => isoToDateKey(s.startAt)))]
  return keys.sort()
})

const slotsForSelectedDay = computed(() =>
  selectedDay.value
    ? slots.value.filter(s => isoToDateKey(s.startAt) === selectedDay.value)
    : []
)

const filteredServices = computed(() => {
  if (!selectedSpecialty.value) return services.value
  return services.value.filter(s => {
    const sp = s.medicalService?.specialty
    return !sp || sp.slug === selectedSpecialty.value.slug
  })
})

const canConfirm = computed(() => selectedService.value && selectedSlot.value && !booking.value)

const bookingErrorMessage = computed(() => {
  if (!bookingError.value) return null
  const map = {
    AUTH_REQUIRED:  t('booking.authRequired'),
    SLOT_UNAVAILABLE: t('booking.slotUnavailable'),
    ALREADY_BOOKED: t('booking.alreadyBooked'),
  }
  return map[bookingError.value] || t('booking.unknownError')
})

// ── Watchers ───────────────────────────────────────────────────────────────
watch(selectedSpecialty, () => {
  selectedService.value = null
  selectedDay.value     = null
  selectedSlot.value    = null
  slots.value           = []
})

watch(selectedService, () => {
  selectedDay.value  = null
  selectedSlot.value = null
  if (selectedService.value) loadAvailability()
})

watch(selectedDay, () => { selectedSlot.value = null })

// ── Helpers ────────────────────────────────────────────────────────────────
function specialtyName(sp) {
  const key = `specialty.${sp?.slug}`
  const t2  = t(key)
  return t2 !== key ? t2 : sp?.name || ''
}

function serviceName(svc) {
  const key = `services.${svc.medicalService?.slug}`
  const t2  = t(key)
  return t2 !== key ? t2 : svc.medicalService?.name || ''
}

function formatPrice(price) {
  const n = parseFloat(price)
  return n % 1 === 0 ? n.toFixed(0) : n.toFixed(2)
}

function formatReviewDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

// ── Fetching ───────────────────────────────────────────────────────────────
async function fetchDoctor() {
  loadingDoctor.value = true
  try {
    const { data } = await api.get(`/api/doctors/${doctorId.value}`)
    doctor.value = data
    if (availableSpecialties.value.length === 1) {
      selectedSpecialty.value = availableSpecialties.value[0]
    }
  } catch {
    notFound.value = true
  } finally {
    loadingDoctor.value = false
  }
}

async function fetchServices() {
  loadingServices.value = true
  try {
    const { data } = await api.get(`/api/doctors/${doctorId.value}/services`)
    services.value = Array.isArray(data) ? data : []
  } catch {
    //
  } finally {
    loadingServices.value = false
  }
}

async function fetchReviews() {
  loadingReviews.value = true
  try {
    const { data } = await api.get(`/api/doctors/${doctorId.value}/reviews`)
    reviews.value = Array.isArray(data) ? data : []
  } catch {
    //
  } finally {
    loadingReviews.value = false
  }
}

async function loadAvailability() {
  loadingSlots.value = true
  slots.value        = []
  selectedDay.value  = null
  selectedSlot.value = null
  try {
    const { data } = await api.get(`/api/doctors/${doctorId.value}/availability`)
    slots.value       = Array.isArray(data) ? data : []
    selectedDay.value = dayOptions.value[0] ?? null
  } catch {
    //
  } finally {
    loadingSlots.value = false
  }
}

// ── Booking ────────────────────────────────────────────────────────────────
function selectService(svc) {
  selectedService.value = svc
  bookingError.value    = null
}

function selectSlot(slot) {
  selectedSlot.value = slot
  bookingError.value = null
}

async function confirmBooking() {
  if (!canConfirm.value) return
  if (!auth.token) { bookingError.value = 'AUTH_REQUIRED'; return }

  booking.value      = true
  bookingError.value = null
  try {
    await api.post('/api/appointments', {
      timeSlotId:      selectedSlot.value.id,
      doctorServiceId: selectedService.value.id,
    })
    showSuccess.value = true
  } catch (e) {
    bookingError.value = e?.response?.data?.error?.code || 'UNKNOWN'
  } finally {
    booking.value = false
  }
}

function goToLogin() {
  router.push({ name: 'login', query: { redirect: route.fullPath } })
}

onMounted(() => {
  Promise.all([fetchDoctor(), fetchServices(), fetchReviews()])
})
</script>

<template>
  <v-container max-width="1100" class="py-6 px-4">

    <!-- Back -->
    <v-btn
      variant="text"
      prepend-icon="mdi-arrow-left"
      :to="{ name: 'doctors' }"
      class="mb-5 pl-1"
    >
      {{ t('doctorProfile.back') }}
    </v-btn>

    <!-- Loading -->
    <div v-if="loadingDoctor" class="d-flex justify-center py-16">
      <v-progress-circular indeterminate color="primary" size="48" />
    </div>

    <!-- Not found -->
    <v-card v-else-if="notFound" flat rounded="xl" class="text-center pa-12">
      <v-icon size="56" color="grey-lighten-1" class="mb-4">mdi-account-off-outline</v-icon>
      <div class="text-h6 font-weight-bold mb-2">{{ t('doctorProfile.notFound') }}</div>
      <div class="text-body-2 text-medium-emphasis mb-5">{{ t('doctorProfile.notFoundHint') }}</div>
      <v-btn color="primary" variant="tonal" :to="{ name: 'doctors' }">
        {{ t('doctorProfile.back') }}
      </v-btn>
    </v-card>

    <template v-else-if="doctor">

      <!-- ── Hero ──────────────────────────────────────────────────────────── -->
      <v-card flat rounded="xl" class="hero-card mb-6 pa-6">
        <div class="d-flex align-start ga-5 flex-wrap">

          <v-avatar size="96" :color="doctor.avatarPath ? undefined : 'primary'" class="hero-avatar flex-shrink-0">
            <v-img v-if="doctor.avatarPath" :src="uploadUrl(doctor.avatarPath)" cover />
            <span v-else class="text-h4 text-white font-weight-bold">{{ doctorInitials }}</span>
          </v-avatar>

          <div class="flex-grow-1">
            <div class="d-flex align-center ga-3 flex-wrap mb-2">
              <h1 class="text-h5 font-weight-bold">Dr. {{ doctorName }}</h1>
              <v-chip v-if="doctor.acceptsInsurance" color="success" size="small" variant="tonal">
                <v-icon start size="13">mdi-shield-check</v-icon>
                {{ t('doctors.insurance') }}
              </v-chip>
            </div>

            <div class="d-flex flex-wrap ga-2 mb-3">
              <v-chip
                v-for="sp in doctor.specialties"
                :key="sp.slug"
                size="small"
                color="primary"
                variant="tonal"
              >
                {{ specialtyName(sp) }}
              </v-chip>
            </div>

            <div class="d-flex align-center ga-1 text-medium-emphasis mb-3">
              <v-icon size="15">mdi-hospital-building</v-icon>
              <span class="text-body-2">{{ doctor.clinic?.name }}</span>
              <span v-if="doctor.clinic?.city" class="text-body-2">&nbsp;· {{ doctor.clinic.city }}</span>
            </div>

            <div class="d-flex align-center ga-4 flex-wrap">
              <div v-if="averageRating" class="d-flex align-center ga-1">
                <v-rating
                  :model-value="averageRating"
                  readonly
                  half-increments
                  density="compact"
                  size="small"
                  color="amber"
                  active-color="amber"
                />
                <span class="text-body-2 font-weight-bold">{{ averageRating }}</span>
                <span class="text-caption text-medium-emphasis">({{ reviews.length }})</span>
              </div>
              <div v-if="startingPrice" class="text-body-2 text-medium-emphasis">
                {{ t('doctors.from') }}
                <strong class="text-primary">{{ formatPrice(startingPrice) }} RON</strong>
              </div>
            </div>
          </div>
        </div>
      </v-card>

      <!-- ── Main grid ─────────────────────────────────────────────────────── -->
      <div class="profile-grid">

        <!-- Left column -->
        <div class="profile-main">

          <!-- Bio -->
          <v-card v-if="doctor.bio" flat rounded="xl" class="info-card mb-4 pa-5">
            <div class="section-label mb-3">{{ t('doctorProfile.about') }}</div>
            <p class="text-body-1 bio-text">{{ doctor.bio }}</p>
          </v-card>

          <!-- Services -->
          <v-card flat rounded="xl" class="info-card mb-4 pa-5">
            <div class="d-flex align-center justify-space-between mb-4">
              <div class="section-label">{{ t('doctorProfile.services') }}</div>
              <v-chip v-if="services.length" size="x-small" color="primary" variant="tonal">
                {{ services.length }}
              </v-chip>
            </div>

            <div v-if="loadingServices" class="d-flex justify-center py-6">
              <v-progress-circular indeterminate color="primary" size="28" />
            </div>
            <div v-else-if="!services.length" class="text-body-2 text-medium-emphasis text-center py-4">
              {{ t('booking.noServices') }}
            </div>
            <div v-else class="services-list">
              <div v-for="svc in services" :key="svc.id" class="service-row">
                <div>
                  <div class="text-body-2 font-weight-semibold">{{ serviceName(svc) }}</div>
                  <div class="text-caption text-medium-emphasis">{{ svc.durationMinutes }} {{ t('booking.min') }}</div>
                </div>
                <div class="text-body-2 font-weight-bold text-primary">{{ formatPrice(svc.price) }} RON</div>
              </div>
            </div>
          </v-card>

          <!-- Reviews -->
          <v-card flat rounded="xl" class="info-card pa-5">
            <div class="d-flex align-center justify-space-between mb-4">
              <div class="section-label">{{ t('doctorProfile.reviews') }}</div>
              <div v-if="averageRating" class="d-flex align-center ga-1">
                <v-icon size="14" color="amber">mdi-star</v-icon>
                <span class="text-body-2 font-weight-bold">{{ averageRating }}</span>
                <span class="text-caption text-medium-emphasis">({{ reviews.length }})</span>
              </div>
            </div>

            <div v-if="loadingReviews" class="d-flex justify-center py-6">
              <v-progress-circular indeterminate color="primary" size="28" />
            </div>
            <div v-else-if="!reviews.length" class="text-center py-6">
              <v-icon size="40" color="grey-lighten-1" class="mb-2">mdi-star-off-outline</v-icon>
              <div class="text-body-2 text-medium-emphasis">{{ t('review.noReviews') }}</div>
            </div>
            <div v-else class="reviews-list">
              <div v-for="review in reviews" :key="review.id" class="review-item">
                <div class="d-flex align-center justify-space-between mb-2">
                  <div class="d-flex align-center ga-2">
                    <v-avatar size="32" color="primary" variant="tonal">
                      <span class="text-caption font-weight-bold">
                        {{ review.authorName.charAt(0).toUpperCase() }}
                      </span>
                    </v-avatar>
                    <div>
                      <div class="text-body-2 font-weight-medium">{{ review.authorName }}</div>
                      <div class="text-caption text-medium-emphasis">{{ formatReviewDate(review.createdAt) }}</div>
                    </div>
                  </div>
                  <v-rating
                    :model-value="review.rating"
                    readonly
                    half-increments
                    density="compact"
                    size="x-small"
                    color="amber"
                    active-color="amber"
                  />
                </div>
                <p v-if="review.comment" class="text-body-2 review-comment mb-0">{{ review.comment }}</p>
              </div>
            </div>
          </v-card>
        </div>

        <!-- Right column (booking + map) -->
        <div class="profile-sidebar">
          <div class="sidebar-sticky">

            <!-- Booking widget -->
            <v-card flat rounded="xl" class="info-card mb-4 pa-5">
              <div class="section-label mb-4">{{ t('doctorProfile.book') }}</div>

              <!-- Success -->
              <div v-if="showSuccess" class="text-center py-4">
                <v-icon color="success" size="52" class="mb-3">mdi-check-circle</v-icon>
                <div class="text-subtitle-1 font-weight-bold">{{ t('booking.booked') }}</div>
                <div class="text-body-2 text-medium-emphasis mb-4">{{ t('booking.bookedSubtitle') }}</div>
                <v-btn color="primary" variant="tonal" rounded="lg" :to="{ name: 'me' }">
                  <v-icon start>mdi-calendar</v-icon>
                  {{ t('profile.appointments') }}
                </v-btn>
              </div>

              <template v-else>
                <!-- Step: Specialty (if multiple) -->
                <div v-if="availableSpecialties.length > 1" class="booking-step">
                  <div class="step-label">
                    <span class="step-num">1</span>
                    {{ t('booking.stepSpecialty') }}
                  </div>
                  <div class="d-flex flex-wrap ga-2 mt-2">
                    <v-chip
                      v-for="sp in availableSpecialties"
                      :key="sp.slug"
                      :color="selectedSpecialty?.slug === sp.slug ? 'primary' : undefined"
                      :variant="selectedSpecialty?.slug === sp.slug ? 'flat' : 'outlined'"
                      size="small"
                      @click="selectedSpecialty = sp"
                    >
                      {{ specialtyName(sp) }}
                    </v-chip>
                  </div>
                </div>

                <!-- Step: Service -->
                <div
                  v-if="availableSpecialties.length <= 1 || selectedSpecialty"
                  class="booking-step"
                >
                  <div class="step-label">
                    <span class="step-num">{{ availableSpecialties.length > 1 ? 2 : 1 }}</span>
                    {{ t('booking.step1') }}
                  </div>
                  <div class="d-flex flex-column ga-2 mt-2">
                    <button
                      v-for="svc in filteredServices"
                      :key="svc.id"
                      type="button"
                      class="service-btn"
                      :class="{ 'service-btn--selected': selectedService?.id === svc.id }"
                      @click="selectService(svc)"
                    >
                      <div>
                        <div class="text-body-2 font-weight-medium">{{ serviceName(svc) }}</div>
                        <div class="text-caption text-medium-emphasis">{{ svc.durationMinutes }} {{ t('booking.min') }}</div>
                      </div>
                      <div class="text-body-2 font-weight-bold text-primary">{{ formatPrice(svc.price) }} RON</div>
                    </button>
                  </div>
                </div>

                <!-- Steps after service selection -->
                <template v-if="selectedService">
                  <div v-if="loadingSlots" class="d-flex justify-center py-4">
                    <v-progress-circular indeterminate color="primary" size="24" />
                  </div>

                  <template v-else-if="dayOptions.length">
                    <!-- Step: Day -->
                    <div class="booking-step">
                      <div class="step-label">
                        <span class="step-num">{{ availableSpecialties.length > 1 ? 3 : 2 }}</span>
                        {{ t('booking.step2') }}
                      </div>
                      <div class="d-flex flex-wrap ga-2 mt-2">
                        <v-chip
                          v-for="day in dayOptions"
                          :key="day"
                          :color="selectedDay === day ? 'primary' : undefined"
                          :variant="selectedDay === day ? 'flat' : 'outlined'"
                          size="small"
                          @click="selectedDay = day"
                        >
                          {{ dateKeyToLabel(day) }}
                        </v-chip>
                      </div>
                    </div>

                    <!-- Step: Time -->
                    <div class="booking-step">
                      <div class="step-label">
                        <span class="step-num">{{ availableSpecialties.length > 1 ? 4 : 3 }}</span>
                        {{ t('booking.step3') }}
                      </div>
                      <div class="time-slots-grid mt-2">
                        <TimeSlotButton
                          v-for="slot in slotsForSelectedDay"
                          :key="slot.id"
                          :slot="slot"
                          :selected="selectedSlot?.id === slot.id"
                          @click="selectSlot(slot)"
                        />
                      </div>
                      <p v-if="!slotsForSelectedDay.length" class="text-caption text-medium-emphasis text-center py-2">
                        {{ t('booking.noSlotsDay') }}
                      </p>
                    </div>
                  </template>

                  <div v-else class="text-center py-4">
                    <v-icon size="36" color="grey-lighten-1">mdi-calendar-remove</v-icon>
                    <div class="text-body-2 text-medium-emphasis mt-1">{{ t('booking.noSlots') }}</div>
                  </div>
                </template>

                <!-- Error -->
                <v-alert
                  v-if="bookingError"
                  :type="bookingError === 'AUTH_REQUIRED' ? 'warning' : 'error'"
                  variant="tonal"
                  density="compact"
                  rounded="lg"
                  class="mb-3"
                >
                  {{ bookingErrorMessage }}
                  <template v-if="bookingError === 'AUTH_REQUIRED'" #append>
                    <v-btn variant="flat" color="warning" size="x-small" @click="goToLogin">
                      {{ t('nav.login') }}
                    </v-btn>
                  </template>
                </v-alert>

                <!-- Confirm button -->
                <v-btn
                  color="primary"
                  block
                  rounded="lg"
                  size="large"
                  :disabled="!canConfirm"
                  :loading="booking"
                  class="mt-2"
                  @click="confirmBooking"
                >
                  <v-icon start>mdi-calendar-check</v-icon>
                  {{ t('booking.confirm') }}
                  <span v-if="selectedService" class="ml-1">
                    &ndash; {{ formatPrice(selectedService.price) }} RON
                  </span>
                </v-btn>
              </template>
            </v-card>

            <!-- Map -->
            <v-card flat rounded="xl" class="info-card overflow-hidden">
              <div class="map-header pa-4">
                <v-icon size="15" class="mr-1">mdi-hospital-building</v-icon>
                <span class="text-body-2 font-weight-medium">{{ t('booking.clinicLocation') }}</span>
              </div>
              <v-divider />
              <div class="map-wrap">
                <ClinicMap
                  :address="doctor.clinic?.address"
                  :city="doctor.clinic?.city"
                  :clinic-name="doctor.clinic?.name"
                />
              </div>
            </v-card>

          </div>
        </div>

      </div>
    </template>
  </v-container>
</template>

<style scoped>
.hero-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.07);
  background: linear-gradient(
    135deg,
    rgba(var(--v-theme-primary), 0.04) 0%,
    rgba(var(--v-theme-surface), 1) 60%
  );
}

.hero-avatar {
  box-shadow: 0 0 0 4px rgba(var(--v-theme-primary), 0.15);
  font-size: 28px;
}

.info-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.07);
}

.section-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.7px;
  color: rgba(var(--v-theme-on-surface), 0.4);
}

.bio-text {
  line-height: 1.75;
  color: rgba(var(--v-theme-on-surface), 0.8);
}

/* ── Profile grid ─────────────────────────────────────────────────────────── */
.profile-grid {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 20px;
  align-items: start;
}

@media (max-width: 959px) {
  .profile-grid {
    grid-template-columns: 1fr;
  }
  .profile-sidebar {
    order: -1;
  }
}

.sidebar-sticky {
  position: sticky;
  top: 76px;
}

/* ── Services ─────────────────────────────────────────────────────────────── */
.services-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.service-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 11px 14px;
  border-radius: 10px;
  background: rgba(var(--v-theme-surface-variant), 0.45);
}

/* ── Reviews ──────────────────────────────────────────────────────────────── */
.reviews-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.review-item {
  padding: 14px;
  border-radius: 12px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.07);
  background: rgba(var(--v-theme-surface-variant), 0.3);
}

.review-comment {
  font-size: 0.875rem;
  line-height: 1.55;
  color: rgba(var(--v-theme-on-surface), 0.78);
}

/* ── Booking widget ───────────────────────────────────────────────────────── */
.booking-step {
  margin-bottom: 18px;
}

.step-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.875rem;
  font-weight: 600;
}

.step-num {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  min-width: 22px;
  background: rgb(var(--v-theme-primary));
  color: #fff;
  border-radius: 50%;
  font-size: 0.7rem;
  font-weight: 700;
}

.service-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 10px;
  background: transparent;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
  text-align: left;
  width: 100%;
}

.service-btn:hover {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.04);
}

.service-btn--selected {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.08);
}

.time-slots-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

/* ── Map ──────────────────────────────────────────────────────────────────── */
.map-header {
  display: flex;
  align-items: center;
}

.map-wrap {
  height: 260px;
  display: flex;
  flex-direction: column;
}
</style>
