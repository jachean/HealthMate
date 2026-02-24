<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import api from '@/lib/api'
import { isoToHHmm, isoToDateKey, formatSlotRange } from '@/utils/date'

const { t, locale } = useI18n()

function isInThePast(iso) {
  return iso && new Date(iso) < new Date()
}
const auth = useAuthStore()
const router = useRouter()

const initials = computed(() => {
  const f = auth.user?.firstName || ''
  const l = auth.user?.lastName || ''
  return (f.charAt(0) + l.charAt(0)).toUpperCase()
})

// ── Appointments ──────────────────────────────────────────────────────────────
const appointments = ref([])
const loading = ref(true)
const fetchError = ref(false)

const page = ref(1)
const perPage = 5

const totalPages = computed(() => Math.ceil(appointments.value.length / perPage))

const paginatedAppointments = computed(() => {
  const start = (page.value - 1) * perPage
  return appointments.value.slice(start, start + perPage)
})

onMounted(async () => {
  try {
    const { data } = await api.get('/api/me/appointments')
    appointments.value = data
  } catch {
    fetchError.value = true
  } finally {
    loading.value = false
  }
})

// ── Detail dialog ─────────────────────────────────────────────────────────────
const selectedAppointment = ref(null)
const dialogOpen = ref(false)

function openDetail(appt) {
  selectedAppointment.value = appt
  dialogOpen.value = true
}

function closeDetail() {
  dialogOpen.value = false
}

// ── Formatting helpers ────────────────────────────────────────────────────────
const dateLocale = computed(() => locale.value === 'ro' ? 'ro-RO' : 'en-GB')

function formatDate(iso) {
  if (!iso) return ''
  const d = new Date(isoToDateKey(iso))
  return d.toLocaleDateString(dateLocale.value, {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

function dateBlock(iso) {
  if (!iso) return { day: '', month: '', weekday: '' }
  const d = new Date(isoToDateKey(iso))
  return {
    day: String(d.getDate()).padStart(2, '0'),
    month: d.toLocaleDateString(dateLocale.value, { month: 'short' }),
    weekday: d.toLocaleDateString(dateLocale.value, { weekday: 'short' }),
  }
}

function statusColor(status) {
  return status === 'booked' ? 'success' : 'error'
}

function logout() {
  auth.logout()
  router.push({ name: 'home' })
}

// ── Review dialog ──────────────────────────────────────────────────────────────
const reviewDialogOpen = ref(false)
const reviewTargetAppt = ref(null)
const reviewRating = ref(0)
const reviewComment = ref('')
const reviewSubmitting = ref(false)
const reviewError = ref(null)
const reviewSuccess = ref(false)
const confirmingDelete = ref(false)
const deletingReview = ref(false)

function openReviewDialog(appt) {
  reviewTargetAppt.value = appt
  reviewRating.value = appt.reviewRating ?? 0
  reviewComment.value = ''
  reviewError.value = null
  reviewSuccess.value = false
  confirmingDelete.value = false
  reviewDialogOpen.value = true
}

function closeReviewDialog() {
  reviewDialogOpen.value = false
  confirmingDelete.value = false
}

async function deleteReview() {
  if (deletingReview.value) return

  deletingReview.value = true
  reviewError.value = null

  const apptId = reviewTargetAppt.value.id

  try {
    await api.delete(`/api/appointments/${apptId}/review`)

    const appt = appointments.value.find(a => a.id === apptId)
    if (appt) {
      appt.reviewId = null
      appt.reviewRating = null
    }

    reviewSuccess.value = true
    setTimeout(() => {
      reviewDialogOpen.value = false
    }, 1500)
  } catch (e) {
    reviewError.value = e?.response?.data?.error?.message || t('review.errorDelete')
    confirmingDelete.value = false
  } finally {
    deletingReview.value = false
  }
}

async function submitReview() {
  if (!reviewRating.value || reviewSubmitting.value) return

  reviewSubmitting.value = true
  reviewError.value = null

  const apptId = reviewTargetAppt.value.id
  const isEdit = reviewTargetAppt.value.reviewId !== null

  try {
    const { data } = isEdit
      ? await api.put(`/api/appointments/${apptId}/review`, { rating: reviewRating.value, comment: reviewComment.value || null })
      : await api.post(`/api/appointments/${apptId}/review`, { rating: reviewRating.value, comment: reviewComment.value || null })

    // Update local appointment state
    const appt = appointments.value.find(a => a.id === apptId)
    if (appt) {
      appt.reviewId = data.id
      appt.reviewRating = data.rating
    }

    reviewSuccess.value = true
    setTimeout(() => {
      reviewDialogOpen.value = false
    }, 1500)
  } catch (e) {
    const code = e?.response?.data?.error?.code
    reviewError.value = code === 'APPOINTMENT_NOT_PAST'
      ? t('review.errorNotPast')
      : (e?.response?.data?.error?.message || t('review.errorSubmit'))
  } finally {
    reviewSubmitting.value = false
  }
}
</script>

<template>
  <v-container max-width="860" class="py-8 px-4">

    <!-- ── Profile card ───────────────────────────────────────────────────── -->
    <v-card flat rounded="xl" class="profile-card mb-8 pa-6">
      <div class="d-flex align-center flex-wrap ga-5">
        <!-- Avatar -->
        <v-avatar size="80" color="primary" class="profile-avatar text-white font-weight-bold flex-shrink-0">
          <span class="avatar-initials">{{ initials }}</span>
        </v-avatar>

        <!-- Info -->
        <div class="flex-grow-1">
          <div class="text-h5 font-weight-bold mb-1">
            {{ auth.user?.firstName }} {{ auth.user?.lastName }}
          </div>
          <div class="d-flex align-center ga-2 flex-wrap">
            <v-chip size="x-small" variant="tonal" color="primary" label>
              @{{ auth.user?.username }}
            </v-chip>
            <span class="text-body-2 text-medium-emphasis">{{ auth.user?.email }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="d-flex flex-column align-end ga-3">
          <v-chip size="small" variant="outlined" label class="text-caption font-weight-medium">
            <v-icon start size="13">mdi-identifier</v-icon>
            {{ t('profile.patientId') }} #{{ auth.user?.id }}
          </v-chip>
          <v-btn
            variant="tonal"
            color="error"
            size="small"
            prepend-icon="mdi-logout"
            rounded="lg"
            @click="logout"
          >
            {{ t('nav.logout') }}
          </v-btn>
        </div>
      </div>
    </v-card>

    <!-- ── Appointments section ───────────────────────────────────────────── -->
    <div class="section-header mb-5">
      <div class="d-flex align-center ga-2 mb-1">
        <v-icon size="20" color="primary">mdi-calendar-clock-outline</v-icon>
        <span class="text-h6 font-weight-bold">{{ t('profile.appointments') }}</span>
        <v-chip v-if="!loading && !fetchError && appointments.length" size="x-small" color="primary" variant="tonal">
          {{ appointments.length }}
        </v-chip>
      </div>
      <div class="text-body-2 text-medium-emphasis">{{ t('profile.appointmentsSubtitle') }}</div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="d-flex justify-center py-16">
      <v-progress-circular indeterminate color="primary" size="36" />
    </div>

    <!-- Error -->
    <v-alert v-else-if="fetchError" type="error" variant="tonal" rounded="xl" class="mb-4">
      {{ t('profile.loadError') }}
    </v-alert>

    <!-- Empty state -->
    <v-card v-else-if="appointments.length === 0" flat rounded="xl" class="empty-card pa-12 text-center">
      <v-icon size="52" color="primary" class="mb-4 empty-icon">mdi-calendar-blank-outline</v-icon>
      <div class="text-subtitle-1 font-weight-semibold mb-2">{{ t('profile.noAppointments') }}</div>
      <div class="text-body-2 text-medium-emphasis mb-5">{{ t('profile.noAppointmentsHint') }}</div>
      <v-btn color="primary" variant="tonal" rounded="lg" :to="{ name: 'doctors' }">
        <v-icon start>mdi-stethoscope</v-icon>
        {{ t('cta.browseDoctors') }}
      </v-btn>
    </v-card>

    <!-- Appointment list -->
    <div v-else class="appointment-list">
      <v-card
        v-for="appt in paginatedAppointments"
        :key="appt.id"
        flat
        rounded="xl"
        class="appt-card mb-3"
      >
        <div class="d-flex align-center ga-4 pa-4" style="cursor: pointer;" @click="openDetail(appt)">

          <!-- Date block -->
          <div class="date-block flex-shrink-0">
            <div class="date-day">{{ dateBlock(appt.startAt).day }}</div>
            <div class="date-month">{{ dateBlock(appt.startAt).month }}</div>
            <div class="date-weekday">{{ dateBlock(appt.startAt).weekday }}</div>
          </div>

          <!-- Main info -->
          <div class="flex-grow-1 overflow-hidden">
            <div class="d-flex align-center ga-2 mb-1 flex-wrap">
              <span class="text-subtitle-2 font-weight-bold">{{ appt.doctorName }}</span>
              <v-chip :color="statusColor(appt.status)" size="x-small" variant="tonal">
                {{ t(`profile.status.${appt.status}`) }}
              </v-chip>
            </div>
            <div class="text-body-2 text-medium-emphasis">{{ appt.serviceName }}</div>
            <div class="d-flex align-center ga-1 mt-1">
              <v-icon size="11" color="medium-emphasis">mdi-map-marker-outline</v-icon>
              <span class="text-caption text-medium-emphasis">{{ appt.clinicName }}</span>
            </div>
          </div>

          <!-- Time + price + chevron -->
          <div class="d-flex flex-column align-end ga-1 flex-shrink-0">
            <div class="appt-time">{{ isoToHHmm(appt.startAt) }}</div>
            <div class="appt-price">
              {{ parseFloat(appt.price).toFixed(2) }}<span class="currency"> RON</span>
            </div>
            <v-icon size="16" class="mt-1" color="medium-emphasis">mdi-chevron-right</v-icon>
          </div>

        </div>

        <!-- Review row -->
        <div
          v-if="appt.status === 'booked' && isInThePast(appt.startAt)"
          class="appt-review-row pa-3 pt-0"
          @click.stop
        >
          <v-divider class="mb-3" />
          <div class="d-flex align-center justify-space-between flex-wrap ga-2">
            <div v-if="appt.reviewId" class="d-flex align-center ga-2">
              <span class="text-caption text-medium-emphasis">{{ t('review.yourRating') }}:</span>
              <v-rating
                :model-value="appt.reviewRating"
                readonly
                half-increments
                density="compact"
                size="x-small"
                color="amber"
                active-color="amber"
              />
            </div>
            <div v-else class="text-caption text-medium-emphasis">{{ t('review.leave') }}</div>
            <v-btn
              size="x-small"
              :color="appt.reviewId ? 'primary' : 'amber-darken-2'"
              :variant="appt.reviewId ? 'tonal' : 'flat'"
              rounded="lg"
              @click="openReviewDialog(appt)"
            >
              <v-icon start size="13">{{ appt.reviewId ? 'mdi-pencil' : 'mdi-star-plus' }}</v-icon>
              {{ appt.reviewId ? t('review.edit') : t('review.leave') }}
            </v-btn>
          </div>
        </div>
      </v-card>

      <v-pagination
        v-if="totalPages > 1"
        v-model="page"
        :length="totalPages"
        density="comfortable"
        rounded="lg"
        color="primary"
        class="mt-4"
      />
    </div>
  </v-container>

  <!-- ── Appointment detail dialog ──────────────────────────────────────────── -->
  <v-dialog
    v-model="dialogOpen"
    max-width="460"
    rounded="xl"
    @after-leave="selectedAppointment = null"
  >
    <v-card v-if="selectedAppointment" rounded="xl" class="detail-card">

      <!-- Header -->
      <div class="detail-header pa-5 pb-4">
        <div class="d-flex align-start justify-space-between ga-3">
          <div>
            <div class="text-subtitle-1 font-weight-bold">{{ t('profile.detail.title') }}</div>
            <div class="text-caption text-medium-emphasis mt-0.5">
              {{ t('profile.detail.bookedOn') }}: {{ formatDate(selectedAppointment.createdAt) }}
            </div>
          </div>
          <div class="d-flex align-center ga-2">
            <v-chip
              :color="statusColor(selectedAppointment.status)"
              variant="tonal"
              size="small"
            >
              {{ t(`profile.status.${selectedAppointment.status}`) }}
            </v-chip>
            <v-btn icon variant="text" size="x-small" @click="closeDetail">
              <v-icon size="18">mdi-close</v-icon>
            </v-btn>
          </div>
        </div>
      </div>

      <v-divider />

      <!-- Body -->
      <div class="pa-5">
        <div class="detail-grid">

          <div class="detail-row">
            <div class="detail-icon-wrap">
              <v-icon size="17" color="primary">mdi-doctor</v-icon>
            </div>
            <div>
              <div class="detail-label">{{ t('profile.detail.doctor') }}</div>
              <div class="detail-value">{{ selectedAppointment.doctorName }}</div>
            </div>
          </div>

          <div class="detail-row">
            <div class="detail-icon-wrap">
              <v-icon size="17" color="primary">mdi-hospital-building</v-icon>
            </div>
            <div>
              <div class="detail-label">{{ t('profile.detail.clinic') }}</div>
              <div class="detail-value">{{ selectedAppointment.clinicName }}</div>
            </div>
          </div>

          <div class="detail-row">
            <div class="detail-icon-wrap">
              <v-icon size="17" color="primary">mdi-clipboard-pulse-outline</v-icon>
            </div>
            <div>
              <div class="detail-label">{{ t('profile.detail.service') }}</div>
              <div class="detail-value">{{ selectedAppointment.serviceName }}</div>
            </div>
          </div>

          <div class="detail-row">
            <div class="detail-icon-wrap">
              <v-icon size="17" color="primary">mdi-calendar-outline</v-icon>
            </div>
            <div>
              <div class="detail-label">{{ t('profile.detail.date') }}</div>
              <div class="detail-value">{{ formatDate(selectedAppointment.startAt) }}</div>
            </div>
          </div>

          <div class="detail-row">
            <div class="detail-icon-wrap">
              <v-icon size="17" color="primary">mdi-clock-outline</v-icon>
            </div>
            <div>
              <div class="detail-label">{{ t('profile.detail.time') }}</div>
              <div class="detail-value">{{ formatSlotRange(selectedAppointment.startAt, selectedAppointment.endAt) }}</div>
            </div>
          </div>

          <div class="detail-row">
            <div class="detail-icon-wrap">
              <v-icon size="17" color="primary">mdi-cash-multiple</v-icon>
            </div>
            <div>
              <div class="detail-label">{{ t('profile.detail.price') }}</div>
              <div class="detail-value font-weight-bold">
                {{ parseFloat(selectedAppointment.price).toFixed(2) }} RON
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Footer -->
      <div class="pa-5 pt-0">
        <v-btn color="primary" variant="tonal" block rounded="lg" @click="closeDetail">
          {{ t('profile.close') }}
        </v-btn>
      </div>

    </v-card>
  </v-dialog>

  <!-- ── Review dialog ────────────────────────────────────────────────────────── -->
  <v-dialog v-model="reviewDialogOpen" max-width="420" rounded="xl">
    <v-card rounded="xl" class="review-dialog-card">
      <!-- Header -->
      <div class="d-flex align-center justify-space-between pa-5 pb-4">
        <div class="text-subtitle-1 font-weight-bold">
          {{ reviewTargetAppt?.reviewId ? t('review.editTitle') : t('review.title') }}
        </div>
        <v-btn icon variant="text" size="x-small" @click="closeReviewDialog">
          <v-icon size="18">mdi-close</v-icon>
        </v-btn>
      </div>

      <v-divider />

      <div class="pa-5">
        <!-- Success state -->
        <div v-if="reviewSuccess" class="d-flex flex-column align-center py-6 text-center">
          <v-icon color="success" size="52" class="mb-3">mdi-check-circle</v-icon>
          <div class="text-subtitle-1 font-weight-bold">
            {{ confirmingDelete ? t('review.deleteSuccess') : t('review.success') }}
          </div>
        </div>

        <!-- Form -->
        <template v-else>
          <div class="text-caption text-medium-emphasis mb-1">{{ t('review.rating') }}</div>
          <div class="d-flex justify-center mb-5">
            <v-rating
              v-model="reviewRating"
              half-increments
              hover
              color="amber"
              active-color="amber"
              size="36"
            />
          </div>

          <v-textarea
            v-model="reviewComment"
            :label="t('review.comment')"
            :placeholder="t('review.commentPlaceholder')"
            rows="3"
            max-rows="5"
            auto-grow
            variant="outlined"
            rounded="lg"
            density="compact"
            :maxlength="500"
            counter
            class="mb-4"
          />

          <v-alert v-if="reviewError" type="error" variant="tonal" density="compact" rounded="lg" class="mb-4">
            {{ reviewError }}
          </v-alert>

          <v-btn
            color="primary"
            block
            rounded="lg"
            :loading="reviewSubmitting"
            :disabled="!reviewRating || reviewSubmitting"
            @click="submitReview"
          >
            {{ t('review.submit') }}
          </v-btn>

          <!-- Delete (edit mode only) -->
          <template v-if="reviewTargetAppt?.reviewId">
            <v-divider class="my-4" />
            <div v-if="!confirmingDelete" class="d-flex justify-center">
              <v-btn
                variant="text"
                color="error"
                size="small"
                prepend-icon="mdi-trash-can-outline"
                @click="confirmingDelete = true"
              >
                {{ t('review.delete') }}
              </v-btn>
            </div>
            <div v-else class="d-flex align-center justify-center ga-2">
              <span class="text-body-2 text-medium-emphasis">{{ t('review.deleteConfirm') }}</span>
              <v-btn
                color="error"
                size="small"
                rounded="lg"
                :loading="deletingReview"
                @click="deleteReview"
              >
                {{ t('review.deleteConfirmBtn') }}
              </v-btn>
              <v-btn
                variant="text"
                size="small"
                rounded="lg"
                :disabled="deletingReview"
                @click="confirmingDelete = false"
              >
                {{ t('profile.cancel') }}
              </v-btn>
            </div>
          </template>
        </template>
      </div>
    </v-card>
  </v-dialog>
</template>

<style scoped>
/* ── Profile card ─────────────────────────────────────────────────────────── */
.profile-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.07);
  background: linear-gradient(
    135deg,
    rgba(var(--v-theme-primary), 0.03) 0%,
    rgba(var(--v-theme-surface), 1) 60%
  );
}

.profile-avatar {
  font-size: 26px;
  box-shadow: 0 0 0 4px rgba(var(--v-theme-primary), 0.12);
}

.avatar-initials {
  font-size: 26px;
  letter-spacing: 1px;
}

/* ── Section header ───────────────────────────────────────────────────────── */
.section-header {
  padding-left: 2px;
}

/* ── Empty state ──────────────────────────────────────────────────────────── */
.empty-card {
  border: 1.5px dashed rgba(var(--v-theme-on-surface), 0.12);
  background: rgba(var(--v-theme-surface-variant), 0.35);
}

.empty-icon {
  opacity: 0.25;
}

/* ── Appointment card ─────────────────────────────────────────────────────── */
.appt-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.07);
  cursor: pointer;
  transition: border-color 0.2s ease, transform 0.18s ease, box-shadow 0.2s ease;
}

.appt-card:hover {
  border-color: rgba(var(--v-theme-primary), 0.28);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(var(--v-theme-primary), 0.07) !important;
}

/* ── Date block ───────────────────────────────────────────────────────────── */
.date-block {
  width: 54px;
  padding: 10px 6px;
  background: rgba(var(--v-theme-primary), 0.06);
  border-radius: 12px;
  text-align: center;
  flex-shrink: 0;
}

.date-day {
  font-size: 22px;
  font-weight: 800;
  line-height: 1;
  color: rgb(var(--v-theme-primary));
}

.date-month {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: rgb(var(--v-theme-primary));
  margin-top: 3px;
}

.date-weekday {
  font-size: 9px;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  color: rgba(var(--v-theme-on-surface), 0.4);
  margin-top: 2px;
}

/* ── Time / price ─────────────────────────────────────────────────────────── */
.appt-time {
  font-size: 13px;
  font-weight: 600;
  color: rgb(var(--v-theme-on-surface));
}

.appt-price {
  font-size: 14px;
  font-weight: 700;
  color: rgb(var(--v-theme-on-surface));
}

.currency {
  font-size: 10px;
  font-weight: 500;
  opacity: 0.55;
}

/* ── Detail dialog ────────────────────────────────────────────────────────── */
.detail-card {
  overflow: hidden;
}

.detail-header {
  background: rgba(var(--v-theme-surface-variant), 0.4);
}

.detail-grid {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.detail-row {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.detail-icon-wrap {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(var(--v-theme-primary), 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 1px;
}

.detail-label {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: rgba(var(--v-theme-on-surface), 0.45);
  margin-bottom: 2px;
}

.detail-value {
  font-size: 14px;
  font-weight: 500;
  color: rgb(var(--v-theme-on-surface));
  line-height: 1.3;
}

/* ── Review row ───────────────────────────────────────────────────────────── */
.appt-review-row {
  background: rgba(var(--v-theme-surface-variant), 0.25);
  border-radius: 0 0 16px 16px;
}

.review-dialog-card {
  overflow: hidden;
}
</style>
