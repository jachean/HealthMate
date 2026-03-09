<script setup>
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/lib/api'
import {
  adminGetUsers,
  adminGetDoctors,
  adminGetDoctorServices,
  adminCreateAppointment,
} from '@/services/adminService'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  modelValue: Boolean,
})
const emit = defineEmits(['update:modelValue', 'saved'])

const { t } = useI18n()
const authStore = useAuthStore()

// Steps: 0=patient, 1=doctor, 2=service, 3=datetime
const step = ref(0)

// Step 0 — Patient
const patientSearch = ref('')
const patientResults = ref([])
const patientLoading = ref(false)
const selectedPatient = ref(null)
let patientTimer = null

// Step 1 — Doctor
const doctors = ref([])
const selectedDoctor = ref(null)
const doctorsLoading = ref(false)
const doctorSearch = ref('')

const filteredDoctors = computed(() => {
  const q = doctorSearch.value.trim().toLowerCase()
  if (!q) return doctors.value
  return doctors.value.filter(d =>
    `${d.firstName} ${d.lastName}`.toLowerCase().includes(q) ||
    d.clinic?.name?.toLowerCase().includes(q)
  )
})

// Step 2 — Service
const services = ref([])
const selectedService = ref(null)
const servicesLoading = ref(false)

// Step 3 — Date & time
const availability = ref([])
const availLoading = ref(false)
const selectedDay = ref(null)
const selectedSlot = ref(null)

const submitting = ref(false)
const submitError = ref('')
const success = ref(false)

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      resetForm()
      loadDoctors()
    }
  }
)

function resetForm() {
  step.value = 0
  patientSearch.value = ''
  patientResults.value = []
  selectedPatient.value = null
  selectedDoctor.value = null
  doctorSearch.value = ''
  services.value = []
  selectedService.value = null
  availability.value = []
  selectedDay.value = null
  selectedSlot.value = null
  submitting.value = false
  submitError.value = ''
  success.value = false
}

// ── Patient search ─────────────────────────────────────────────────────────────
function onPatientInput() {
  clearTimeout(patientTimer)
  patientTimer = setTimeout(searchPatients, 300)
}

async function searchPatients() {
  if (!patientSearch.value.trim()) {
    patientResults.value = []
    return
  }
  patientLoading.value = true
  try {
    const res = await adminGetUsers({ search: patientSearch.value, limit: 10 })
    patientResults.value = res.data
  } catch {
    patientResults.value = []
  } finally {
    patientLoading.value = false
  }
}

function selectPatient(user) {
  selectedPatient.value = user
  step.value = 1
}

// ── Doctors ───────────────────────────────────────────────────────────────────
async function loadDoctors() {
  doctorsLoading.value = true
  try {
    // Clinic admins are scoped — backend returns only their clinic's doctors
    const res = await adminGetDoctors({ limit: 500 })
    doctors.value = res.data
  } catch {
    doctors.value = []
  } finally {
    doctorsLoading.value = false
  }
}

async function selectDoctor(doctor) {
  selectedDoctor.value = doctor
  services.value = []
  selectedService.value = null
  servicesLoading.value = true
  try {
    services.value = await adminGetDoctorServices(doctor.id)
  } catch {
    services.value = []
  } finally {
    servicesLoading.value = false
    step.value = 2
  }
}

// ── Service ───────────────────────────────────────────────────────────────────
async function selectService(svc) {
  selectedService.value = svc
  availability.value = []
  selectedDay.value = null
  selectedSlot.value = null
  availLoading.value = true
  try {
    const res = await api.get(`/api/admin/doctors/${selectedDoctor.value.id}/availability`)
    availability.value = res.data
  } catch {
    availability.value = []
  } finally {
    availLoading.value = false
    step.value = 3
  }
}

// ── Date & time ───────────────────────────────────────────────────────────────
const days = computed(() => {
  const seen = new Set()
  return availability.value
    .filter(s => {
      const d = s.startAt.slice(0, 10)
      if (seen.has(d)) return false
      seen.add(d)
      return true
    })
    .map(s => s.startAt.slice(0, 10))
})

const slotsForDay = computed(() => {
  if (!selectedDay.value) return []
  return availability.value.filter(s => s.startAt.startsWith(selectedDay.value))
})

function formatDay(iso) {
  return new Date(iso + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' })
}

function formatTime(iso) {
  return new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
}

// ── Submit ─────────────────────────────────────────────────────────────────────
async function confirm() {
  if (!selectedPatient.value || !selectedSlot.value || !selectedService.value) return
  submitting.value = true
  submitError.value = ''
  try {
    await adminCreateAppointment({
      userId: selectedPatient.value.id,
      timeSlotId: selectedSlot.value.id,
      doctorServiceId: selectedService.value.id,
    })
    success.value = true
    emit('saved')
    setTimeout(() => {
      emit('update:modelValue', false)
    }, 1500)
  } catch (e) {
    const status = e?.response?.status
    submitError.value = status === 409
      ? t('admin.appointments.create.errorSlotTaken')
      : t('admin.appointments.create.errorCreate')
  } finally {
    submitting.value = false
  }
}

function close() {
  emit('update:modelValue', false)
}
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
        <span>{{ t('admin.appointments.create.title') }}</span>
        <v-btn icon variant="text" size="small" @click="close">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-4">
        <!-- Success -->
        <div v-if="success" class="text-center py-6">
          <v-icon size="56" color="success" class="mb-3">mdi-check-circle-outline</v-icon>
          <div class="text-h6">{{ t('admin.appointments.create.success') }}</div>
        </div>

        <template v-else>
          <!-- Stepper indicator -->
          <div class="appt-stepper mb-5">
            <template v-for="(label, i) in [
              t('admin.appointments.create.selectPatient'),
              t('admin.appointments.create.selectDoctor'),
              t('admin.appointments.create.selectService'),
              t('admin.appointments.create.selectDate'),
            ]" :key="i">
              <div class="appt-step" :class="{ 'is-active': step === i, 'is-done': step > i }">
                <div class="appt-step__dot">
                  <v-icon v-if="step > i" size="12">mdi-check</v-icon>
                  <span v-else>{{ i + 1 }}</span>
                </div>
                <span class="appt-step__label">{{ label }}</span>
              </div>
              <div v-if="i < 3" class="appt-step__line" :class="{ 'is-done': step > i }" />
            </template>
          </div>

          <!-- Step 0: Patient search -->
          <template v-if="step === 0">
            <v-text-field
              v-model="patientSearch"
              :label="t('admin.appointments.create.searchPatient')"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              clearable
              autofocus
              class="mb-3"
              @input="onPatientInput"
            />

            <div v-if="patientLoading" class="d-flex justify-center py-4">
              <v-progress-circular indeterminate color="primary" size="28" />
            </div>

            <v-list v-else-if="patientResults.length" rounded="lg" border lines="two" class="mb-2">
              <v-list-item
                v-for="user in patientResults"
                :key="user.id"
                :title="`${user.firstName} ${user.lastName}`"
                :subtitle="user.email"
                prepend-icon="mdi-account"
                @click="selectPatient(user)"
              />
            </v-list>

            <p v-else-if="patientSearch && !patientLoading" class="text-body-2 text-medium-emphasis">
              {{ t('admin.appointments.create.noPatients') }}
            </p>
          </template>

          <!-- Step 1: Doctor -->
          <template v-if="step === 1">
            <div class="d-flex align-center ga-2 mb-3">
              <v-btn size="small" variant="text" prepend-icon="mdi-arrow-left" @click="step = 0">
                {{ selectedPatient?.firstName }} {{ selectedPatient?.lastName }}
              </v-btn>
            </div>

            <v-text-field
              v-model="doctorSearch"
              :label="t('admin.appointments.create.searchDoctor')"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              clearable
              autofocus
              class="mb-3"
            />

            <div v-if="doctorsLoading" class="d-flex justify-center py-4">
              <v-progress-circular indeterminate color="primary" size="28" />
            </div>

            <template v-else>
              <v-list rounded="lg" border lines="two" style="max-height: 320px; overflow-y: auto;">
                <v-list-item
                  v-for="doc in filteredDoctors"
                  :key="doc.id"
                  :subtitle="doc.clinic?.name"
                  prepend-icon="mdi-doctor"
                  @click="selectDoctor(doc)"
                >
                  <template #title>
                    <span>Dr. {{ doc.firstName }} {{ doc.lastName }}</span>
                    <v-chip
                      v-if="!doc.isActive"
                      size="x-small"
                      color="warning"
                      variant="tonal"
                      class="ml-2"
                    >{{ t('admin.doctors.inactive') }}</v-chip>
                  </template>
                </v-list-item>
                <v-list-item v-if="filteredDoctors.length === 0" disabled>
                  <v-list-item-title class="text-medium-emphasis text-body-2">
                    {{ t('admin.appointments.create.noDoctors') }}
                  </v-list-item-title>
                </v-list-item>
              </v-list>
            </template>
          </template>

          <!-- Step 2: Service -->
          <template v-if="step === 2">
            <div class="d-flex align-center ga-2 mb-3">
              <v-btn size="small" variant="text" prepend-icon="mdi-arrow-left" @click="step = 1">
                Dr. {{ selectedDoctor?.firstName }} {{ selectedDoctor?.lastName }}
              </v-btn>
            </div>

            <div v-if="servicesLoading" class="d-flex justify-center py-4">
              <v-progress-circular indeterminate color="primary" size="28" />
            </div>

            <v-list v-else rounded="lg" border lines="two">
              <v-list-item
                v-for="svc in services"
                :key="svc.id"
                :title="svc.medicalService?.name ?? svc.name"
                :subtitle="`${svc.price} RON · ${svc.durationMinutes} min`"
                prepend-icon="mdi-stethoscope"
                @click="selectService(svc)"
              />
            </v-list>
          </template>

          <!-- Step 3: Date & time -->
          <template v-if="step === 3">
            <div class="d-flex align-center ga-2 mb-3">
              <v-btn size="small" variant="text" prepend-icon="mdi-arrow-left" @click="step = 2">
                {{ selectedService?.medicalService?.name ?? selectedService?.name }}
              </v-btn>
            </div>

            <div v-if="availLoading" class="d-flex justify-center py-4">
              <v-progress-circular indeterminate color="primary" size="28" />
            </div>

            <template v-else-if="days.length">
              <!-- Day strip (horizontal scroll) -->
              <div class="day-strip mb-4">
                <button
                  v-for="day in days"
                  :key="day"
                  type="button"
                  class="day-card"
                  :class="{ 'day-card--selected': selectedDay === day }"
                  @click="selectedDay = day; selectedSlot = null"
                >
                  <span class="day-card__weekday">{{ new Date(day + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'short' }) }}</span>
                  <span class="day-card__num">{{ new Date(day + 'T00:00:00').getDate() }}</span>
                  <span class="day-card__month">{{ new Date(day + 'T00:00:00').toLocaleDateString(undefined, { month: 'short' }) }}</span>
                </button>
              </div>

              <!-- Time slots -->
              <template v-if="selectedDay">
                <p class="text-caption text-medium-emphasis mb-2">{{ t('admin.appointments.create.selectTime') }}</p>
                <div v-if="slotsForDay.length" class="time-grid">
                  <button
                    v-for="slot in slotsForDay"
                    :key="slot.id"
                    type="button"
                    class="time-btn"
                    :class="{ 'time-btn--selected': selectedSlot?.id === slot.id }"
                    @click="selectedSlot = slot"
                  >
                    {{ formatTime(slot.startAt) }}
                  </button>
                </div>
                <p v-else class="text-body-2 text-medium-emphasis">{{ t('admin.appointments.create.noSlotsDay') }}</p>
              </template>
            </template>

            <p v-else class="text-body-2 text-medium-emphasis">{{ t('admin.appointments.create.noSlots') }}</p>

            <!-- Confirm -->
            <div v-if="selectedSlot" class="mt-4">
              <v-alert v-if="submitError" type="error" density="compact" class="mb-3">{{ submitError }}</v-alert>
              <v-btn
                color="primary"
                block
                :loading="submitting"
                @click="confirm"
              >
                {{ t('admin.appointments.create.confirm') }}
              </v-btn>
            </div>
          </template>
        </template>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<style scoped>
/* ── Stepper ─────────────────────────────────────────────────────── */
.appt-stepper {
  display: flex;
  align-items: center;
}

.appt-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}

.appt-step__dot {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.72rem;
  font-weight: 700;
  border: 2px solid rgba(var(--v-theme-on-surface), 0.15);
  color: rgba(var(--v-theme-on-surface), 0.35);
  background: transparent;
  transition: all 0.2s;
}

.appt-step.is-active .appt-step__dot {
  border-color: rgb(var(--v-theme-primary));
  background: rgb(var(--v-theme-primary));
  color: #fff;
}

.appt-step.is-done .appt-step__dot {
  border-color: rgb(var(--v-theme-success));
  background: rgb(var(--v-theme-success));
  color: #fff;
}

.appt-step__label {
  font-size: 0.65rem;
  font-weight: 500;
  color: rgba(var(--v-theme-on-surface), 0.35);
  text-align: center;
  white-space: nowrap;
  max-width: 72px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.appt-step.is-active .appt-step__label,
.appt-step.is-done .appt-step__label {
  color: rgba(var(--v-theme-on-surface), 0.75);
}

.appt-step__line {
  flex: 1;
  height: 2px;
  background: rgba(var(--v-theme-on-surface), 0.1);
  margin: 0 4px;
  margin-bottom: 16px;
  transition: background 0.2s;
}

.appt-step__line.is-done {
  background: rgb(var(--v-theme-success));
}

/* ── Day strip ───────────────────────────────────────────────────── */
.day-strip {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 4px;
  scrollbar-width: thin;
}

.day-strip::-webkit-scrollbar { height: 4px; }
.day-strip::-webkit-scrollbar-track { background: transparent; }
.day-strip::-webkit-scrollbar-thumb {
  background: rgba(var(--v-theme-on-surface), 0.15);
  border-radius: 2px;
}

.day-card {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1px;
  padding: 8px 10px;
  border-radius: 10px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  background: transparent;
  cursor: pointer;
  transition: all 0.15s;
  min-width: 52px;
}

.day-card:hover {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.06);
}

.day-card--selected {
  border-color: rgb(var(--v-theme-primary));
  background: rgb(var(--v-theme-primary));
}

.day-card__weekday {
  font-size: 0.65rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: rgba(var(--v-theme-on-surface), 0.5);
}

.day-card--selected .day-card__weekday { color: rgba(255, 255, 255, 0.8); }

.day-card__num {
  font-size: 1.1rem;
  font-weight: 700;
  line-height: 1;
  color: rgba(var(--v-theme-on-surface), 0.85);
}

.day-card--selected .day-card__num { color: #fff; }

.day-card__month {
  font-size: 0.65rem;
  color: rgba(var(--v-theme-on-surface), 0.45);
}

.day-card--selected .day-card__month { color: rgba(255, 255, 255, 0.75); }

/* ── Time grid ───────────────────────────────────────────────────── */
.time-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.time-btn {
  padding: 8px 4px;
  border-radius: 8px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  background: transparent;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s;
  color: rgba(var(--v-theme-on-surface), 0.85);
}

.time-btn:hover {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.06);
  color: rgb(var(--v-theme-primary));
}

.time-btn--selected {
  border-color: rgb(var(--v-theme-primary));
  background: rgb(var(--v-theme-primary));
  color: #fff;
}
</style>
