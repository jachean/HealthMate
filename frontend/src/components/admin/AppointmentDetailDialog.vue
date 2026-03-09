<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminGetAppointment } from '@/services/adminService'

const props = defineProps({
  modelValue: Boolean,
  appointmentId: { type: Number, default: null },
})
const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()
const detail = ref(null)
const loading = ref(false)
const error = ref('')

watch(
  () => props.modelValue,
  async (open) => {
    if (open && props.appointmentId) {
      loading.value = true
      error.value = ''
      detail.value = null
      try {
        detail.value = await adminGetAppointment(props.appointmentId)
      } catch {
        error.value = t('admin.errors.loadFailed')
      } finally {
        loading.value = false
      }
    }
  }
)

function initials(name) {
  return name
    .split(' ')
    .map(p => p[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

function formatDate(iso) {
  return new Date(iso).toLocaleDateString(undefined, { day: '2-digit', month: 'long', year: 'numeric' })
}

function formatTime(iso) {
  return new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
}

function formatDateTime(iso) {
  return new Date(iso).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}

const statusColor = (s) => s === 'booked' ? 'success' : 'default'
const statusVariant = (s) => s === 'booked' ? 'tonal' : 'outlined'
const statusLabel = (s) => s === 'booked'
  ? t('admin.appointments.statusBooked')
  : t('admin.appointments.statusCancelled')
</script>

<template>
  <v-dialog
    :model-value="modelValue"
    max-width="560"
    scrollable
    @update:model-value="emit('update:modelValue', $event)"
  >
    <v-card rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between pa-4 pb-3">
        <span>{{ t('admin.appointments.detail.title') }}</span>
        <v-btn icon variant="text" size="small" @click="emit('update:modelValue', false)">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-4">
        <!-- Loading -->
        <div v-if="loading" class="d-flex justify-center py-8">
          <v-progress-circular indeterminate color="primary" />
        </div>

        <!-- Error -->
        <v-alert v-else-if="error" type="error" density="compact">{{ error }}</v-alert>

        <!-- Content -->
        <template v-else-if="detail">
          <!-- Status + datetime row -->
          <div class="d-flex align-center justify-space-between mb-4">
            <div>
              <div class="text-h6 font-weight-bold">{{ formatDate(detail.startAt) }}</div>
              <div class="text-body-2 text-medium-emphasis">
                {{ formatTime(detail.startAt) }} – {{ formatTime(detail.endAt) }}
              </div>
            </div>
            <v-chip
              :color="statusColor(detail.status)"
              :variant="statusVariant(detail.status)"
              size="small"
            >
              {{ statusLabel(detail.status) }}
            </v-chip>
          </div>

          <!-- Patient -->
          <div class="info-section mb-3">
            <div class="info-label text-caption text-medium-emphasis text-uppercase font-weight-medium mb-2">
              {{ t('admin.appointments.detail.patient') }}
            </div>
            <div class="d-flex align-center ga-3">
              <v-avatar color="primary" variant="tonal" size="40">
                <span class="text-body-2 font-weight-bold">{{ initials(detail.patient.name) }}</span>
              </v-avatar>
              <div>
                <div class="text-body-1 font-weight-medium">{{ detail.patient.name }}</div>
                <div class="text-caption text-medium-emphasis">{{ detail.patient.email }}</div>
              </div>
            </div>
          </div>

          <!-- Doctor -->
          <div class="info-section mb-3">
            <div class="info-label text-caption text-medium-emphasis text-uppercase font-weight-medium mb-2">
              {{ t('admin.appointments.detail.doctor') }}
            </div>
            <div class="d-flex align-center ga-3">
              <v-avatar color="secondary" variant="tonal" size="40">
                <v-icon size="20">mdi-doctor</v-icon>
              </v-avatar>
              <div>
                <div class="text-body-1 font-weight-medium">Dr. {{ detail.doctor.name }}</div>
                <div class="text-caption text-medium-emphasis d-flex align-center ga-1">
                  <v-icon size="12">mdi-hospital-building</v-icon>
                  {{ detail.doctor.clinic }}
                </div>
              </div>
            </div>
          </div>

          <!-- Service -->
          <div class="info-section mb-3">
            <div class="info-label text-caption text-medium-emphasis text-uppercase font-weight-medium mb-2">
              {{ t('admin.appointments.detail.service') }}
            </div>
            <div class="service-box pa-3 rounded-lg">
              <div class="d-flex align-center justify-space-between">
                <div class="text-body-2 font-weight-medium">{{ detail.service.name }}</div>
                <div class="d-flex align-center ga-3">
                  <div class="text-caption text-medium-emphasis">
                    <v-icon size="12" class="mr-1">mdi-clock-outline</v-icon>
                    {{ detail.service.duration }} {{ t('admin.appointments.detail.min') }}
                  </div>
                  <div class="text-body-2 font-weight-bold text-primary">{{ detail.service.price }} RON</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Booked on -->
          <div class="d-flex align-center ga-1 mb-4">
            <v-icon size="14" class="text-medium-emphasis">mdi-calendar-plus</v-icon>
            <span class="text-caption text-medium-emphasis">
              {{ t('admin.appointments.detail.bookedOn') }}:
              {{ formatDateTime(detail.createdAt) }}
            </span>
          </div>

          <!-- Review -->
          <div class="info-section">
            <div class="info-label text-caption text-medium-emphasis text-uppercase font-weight-medium mb-2">
              {{ t('admin.appointments.detail.review') }}
            </div>
            <template v-if="detail.review">
              <v-rating
                :model-value="detail.review.rating"
                readonly
                half-increments
                density="compact"
                size="small"
                color="warning"
                active-color="warning"
                class="mb-2"
              />
              <p v-if="detail.review.comment" class="text-body-2 text-medium-emphasis mb-0">
                "{{ detail.review.comment }}"
              </p>
            </template>
            <p v-else class="text-body-2 text-medium-emphasis mb-0">
              {{ t('admin.appointments.detail.noReview') }}
            </p>
          </div>
        </template>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.info-section {
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  margin-bottom: 12px;
}

.info-section:last-child {
  border-bottom: none;
  padding-bottom: 0;
  margin-bottom: 0;
}

.service-box {
  background: rgba(var(--v-theme-on-surface), 0.05);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.1);
}
</style>
