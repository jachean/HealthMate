<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { doctorGetAppointments, doctorStartAppointment, doctorCompleteAppointment, doctorCancelAppointment, doctorPauseAppointment, doctorReopenAppointment } from '@/services/doctorService'

const { t } = useI18n()

const appointments = ref([])
const loading = ref(false)
const errorMsg = ref('')
const now = ref(new Date())

// Polling and clock intervals
let pollInterval = null
let clockInterval = null

// Format a Date as YYYY-MM-DD in LOCAL time (avoids UTC offset bugs)
function toLocalDateStr(d) {
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// Date navigation
const todayStr = toLocalDateStr(new Date())
const selectedDate = ref(todayStr)
const datePickerOpen = ref(false)

const isToday = computed(() => selectedDate.value === todayStr)

const formattedDate = computed(() => {
  const d = new Date(selectedDate.value + 'T00:00:00')
  return d.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
})

function prevDay() {
  const d = new Date(selectedDate.value + 'T00:00:00')
  d.setDate(d.getDate() - 1)
  selectedDate.value = toLocalDateStr(d)
}

function nextDay() {
  const d = new Date(selectedDate.value + 'T00:00:00')
  d.setDate(d.getDate() + 1)
  selectedDate.value = toLocalDateStr(d)
}

function goToToday() {
  selectedDate.value = todayStr
}

function onDatePicked(val) {
  if (!val) return
  if (val instanceof Date) {
    selectedDate.value = toLocalDateStr(val)
  } else {
    selectedDate.value = String(val).slice(0, 10)
  }
  datePickerOpen.value = false
}

// Start consultation state
const startingId = ref(null)
const startError = ref('')

// Complete consultation state
const completingId = ref(null)
const completeError = ref('')
const delayResultDialog = ref(false)
const delayResult = ref(null)

// Pause state
const pausingId = ref(null)
const pauseError = ref('')

// Reopen state
const reopeningId = ref(null)
const reopenError = ref('')

// Cancel state
const cancelDialog = ref(false)
const cancelTarget = ref(null)
const cancellingId = ref(null)
const cancelError = ref('')

// Computed: the "next" booked appointment (first by time)
const nextBookedId = computed(() => {
  const booked = appointments.value.find(a => a.status === 'booked')
  return booked?.id ?? null
})

// Computed: current in-progress appointment
const inProgressAppointment = computed(() =>
  appointments.value.find(a => a.status === 'in_progress') ?? null
)

// Computed: minutes over time for in-progress appointment
const overTimeMinutes = computed(() => {
  if (!inProgressAppointment.value) return 0
  const effectiveEnd = new Date(inProgressAppointment.value.effectiveEndAt)
  const diff = now.value - effectiveEnd
  return diff > 0 ? Math.floor(diff / 60000) : 0
})

const isOverTime = computed(() => isToday.value && overTimeMinutes.value > 0)

async function fetchAppointments() {
  loading.value = true
  errorMsg.value = ''
  try {
    appointments.value = await doctorGetAppointments(selectedDate.value)
  } catch {
    errorMsg.value = t('doctor.loadError')
  } finally {
    loading.value = false
  }
}

watch(selectedDate, () => {
  clearInterval(pollInterval)
  pollInterval = null
  fetchAppointments()
  if (isToday.value) {
    pollInterval = setInterval(fetchAppointments, 30000)
  }
})

async function startConsultation(id) {
  startingId.value = id
  startError.value = ''
  try {
    const updated = await doctorStartAppointment(id)
    const idx = appointments.value.findIndex(a => a.id === id)
    if (idx !== -1) appointments.value[idx] = updated
  } catch {
    startError.value = t('doctor.errorAction')
  } finally {
    startingId.value = null
  }
}

async function endConsultation(id) {
  completingId.value = id
  completeError.value = ''
  try {
    const result = await doctorCompleteAppointment(id)
    delayResult.value = result
    delayResultDialog.value = true
    await fetchAppointments()
  } catch {
    completeError.value = t('doctor.errorAction')
  } finally {
    completingId.value = null
  }
}

async function pauseConsultation(id) {
  pausingId.value = id
  pauseError.value = ''
  try {
    const updated = await doctorPauseAppointment(id)
    const idx = appointments.value.findIndex(a => a.id === id)
    if (idx !== -1) appointments.value[idx] = updated
  } catch {
    pauseError.value = t('doctor.errorAction')
  } finally {
    pausingId.value = null
  }
}

async function reopenConsultation(id) {
  reopeningId.value = id
  reopenError.value = ''
  try {
    const updated = await doctorReopenAppointment(id)
    const idx = appointments.value.findIndex(a => a.id === id)
    if (idx !== -1) appointments.value[idx] = updated
  } catch {
    reopenError.value = t('doctor.errorAction')
  } finally {
    reopeningId.value = null
  }
}

function openCancelDialog(appt) {
  cancelTarget.value = appt
  cancelError.value = ''
  cancelDialog.value = true
}

async function confirmCancel() {
  if (!cancelTarget.value) return
  cancellingId.value = cancelTarget.value.id
  cancelError.value = ''
  try {
    const updated = await doctorCancelAppointment(cancelTarget.value.id)
    const idx = appointments.value.findIndex(a => a.id === cancelTarget.value.id)
    if (idx !== -1) appointments.value[idx] = updated
    cancelDialog.value = false
    cancelTarget.value = null
  } catch {
    cancelError.value = t('doctor.errorAction')
  } finally {
    cancellingId.value = null
  }
}

function formatTime(isoString) {
  if (!isoString) return '—'
  return new Date(isoString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

function statusColor(status) {
  switch (status) {
    case 'booked': return 'primary'
    case 'in_progress': return 'warning'
    case 'completed': return 'success'
    case 'cancelled': return 'error'
    default: return 'default'
  }
}

function statusLabel(status) {
  return t(`doctor.status.${status}`)
}

onMounted(() => {
  fetchAppointments()
  pollInterval = setInterval(fetchAppointments, 30000)
  clockInterval = setInterval(() => { now.value = new Date() }, 1000)
})

onUnmounted(() => {
  clearInterval(pollInterval)
  clearInterval(clockInterval)
})
</script>

<template>
  <!-- Over-time warning banner (today only) -->
  <v-alert
    v-if="isOverTime"
    type="error"
    variant="tonal"
    prominent
    class="mb-5"
    icon="mdi-clock-alert"
  >
    <strong>{{ t('doctor.overTimeWarning', { minutes: overTimeMinutes }) }}</strong>
  </v-alert>

  <!-- Header -->
  <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-5">
    <div class="d-flex align-center ga-3">
      <v-avatar color="teal" variant="tonal" rounded="lg" size="44">
        <v-icon size="24">mdi-calendar-month</v-icon>
      </v-avatar>
      <div>
        <h1 class="text-h5 font-weight-bold">{{ t('doctor.schedule') }}</h1>
        <div v-if="isToday" class="text-body-2 text-medium-emphasis">
          {{ t('doctor.todayLabel') }}
        </div>
      </div>
    </div>

    <!-- Date navigation + refresh -->
    <div class="d-flex align-center flex-wrap ga-2">
      <v-btn
        icon="mdi-chevron-left"
        variant="text"
        size="small"
        density="comfortable"
        @click="prevDay"
      />

      <v-menu v-model="datePickerOpen" :close-on-content-click="false">
        <template #activator="{ props }">
          <v-btn
            v-bind="props"
            variant="tonal"
            :color="isToday ? 'teal' : 'default'"
            prepend-icon="mdi-calendar"
            size="small"
          >
            {{ formattedDate }}
          </v-btn>
        </template>
        <v-date-picker
          :model-value="selectedDate"
          hide-header
          @update:model-value="onDatePicked"
        />
      </v-menu>

      <v-btn
        icon="mdi-chevron-right"
        variant="text"
        size="small"
        density="comfortable"
        @click="nextDay"
      />

      <v-btn
        v-if="!isToday"
        variant="tonal"
        color="teal"
        size="small"
        prepend-icon="mdi-calendar-today"
        @click="goToToday"
      >
        {{ t('doctor.today') }}
      </v-btn>

      <v-btn
        variant="tonal"
        color="teal"
        prepend-icon="mdi-refresh"
        size="small"
        :loading="loading"
        @click="fetchAppointments"
      >
        {{ t('doctor.refresh') }}
      </v-btn>
    </div>
  </div>

  <v-alert v-if="errorMsg" type="error" density="compact" class="mb-4">{{ errorMsg }}</v-alert>

  <!-- Viewing past/future hint -->
  <v-alert
    v-if="!isToday && !loading && !errorMsg"
    type="info"
    variant="tonal"
    density="compact"
    icon="mdi-information-outline"
    class="mb-4"
  >
    {{ t('doctor.viewingDate', { date: formattedDate }) }}
  </v-alert>

  <!-- Empty state -->
  <v-card v-if="!loading && appointments.length === 0" variant="outlined" class="pa-8 text-center">
    <v-icon size="48" color="medium-emphasis" class="mb-3">mdi-calendar-blank</v-icon>
    <div class="text-h6 text-medium-emphasis">{{ t('doctor.noAppointments') }}</div>
    <div class="text-body-2 text-medium-emphasis mt-1">{{ t('doctor.noAppointmentsHint') }}</div>
  </v-card>

  <!-- Appointments timeline -->
  <div v-else class="d-flex flex-column ga-3">
    <v-card
      v-for="appt in appointments"
      :key="appt.id"
      variant="outlined"
      :class="[
        'pa-4',
        appt.status === 'in_progress' ? 'border-warning' : '',
        appt.status === 'completed' ? 'opacity-70' : '',
        appt.status === 'cancelled' ? 'opacity-50' : '',
      ]"
    >
      <div class="d-flex align-start justify-space-between ga-4">
        <!-- Left: Time column -->
        <div class="text-center flex-shrink-0" style="min-width: 72px">
          <div class="text-h6 font-weight-bold">{{ formatTime(appt.slotStartAt) }}</div>
          <div v-if="appt.delayMinutes > 0" class="text-caption text-warning font-weight-medium">
            {{ formatTime(appt.effectiveStartAt) }}
          </div>
          <div class="text-caption text-medium-emphasis">{{ appt.durationMinutes }}{{ t('doctor.min') }}</div>
        </div>

        <!-- Center: Appointment info -->
        <div class="flex-grow-1">
          <div class="d-flex align-center ga-2 flex-wrap mb-1">
            <span class="text-body-1 font-weight-bold">{{ appt.patientName }}</span>
            <v-chip :color="statusColor(appt.status)" variant="tonal" size="x-small">
              {{ statusLabel(appt.status) }}
            </v-chip>
          </div>
          <div class="text-body-2 text-medium-emphasis">{{ appt.serviceName }}</div>

          <!-- Delay badge -->
          <v-chip
            v-if="appt.delayMinutes > 0"
            color="amber"
            variant="tonal"
            size="x-small"
            class="mt-1"
            prepend-icon="mdi-clock-alert"
          >
            {{ t('doctor.delayed', { minutes: appt.delayMinutes }) }}
            &nbsp;·&nbsp;
            {{ t('doctor.effectiveTime') }}: {{ formatTime(appt.effectiveStartAt) }}
          </v-chip>

          <!-- Over-time indicator for in-progress (today only) -->
          <div v-if="appt.status === 'in_progress' && isOverTime" class="text-caption text-error mt-1">
            {{ t('doctor.overTimeMinutes', { minutes: overTimeMinutes }) }}
          </div>

          <!-- Errors -->
          <v-alert v-if="appt.status === 'booked' && startError && startingId === null" type="error" density="compact" class="mt-2 text-caption">{{ startError }}</v-alert>
          <v-alert v-if="appt.status === 'in_progress' && completeError && completingId === null" type="error" density="compact" class="mt-2 text-caption">{{ completeError }}</v-alert>
          <v-alert v-if="appt.status === 'in_progress' && pauseError && pausingId === null" type="error" density="compact" class="mt-2 text-caption">{{ pauseError }}</v-alert>
          <v-alert v-if="appt.status === 'completed' && reopenError && reopeningId === null" type="error" density="compact" class="mt-2 text-caption">{{ reopenError }}</v-alert>
        </div>

        <!-- Right: Action buttons -->
        <div class="d-flex flex-column ga-2 flex-shrink-0">
          <!-- Start (today only, first booked, no in_progress running) -->
          <v-btn
            v-if="isToday && appt.status === 'booked' && appt.id === nextBookedId && !inProgressAppointment"
            color="primary"
            variant="tonal"
            size="small"
            prepend-icon="mdi-play"
            :loading="startingId === appt.id"
            @click="startConsultation(appt.id)"
          >
            {{ t('doctor.startConsultation') }}
          </v-btn>

          <!-- End (today only) -->
          <v-btn
            v-if="isToday && appt.status === 'in_progress'"
            color="success"
            variant="tonal"
            size="small"
            prepend-icon="mdi-check"
            :loading="completingId === appt.id"
            @click="endConsultation(appt.id)"
          >
            {{ t('doctor.endConsultation') }}
          </v-btn>

          <!-- Pause (today only, in_progress → revert to booked) -->
          <v-btn
            v-if="isToday && appt.status === 'in_progress'"
            color="warning"
            variant="text"
            size="small"
            icon="mdi-pause"
            :loading="pausingId === appt.id"
            :title="t('doctor.pauseConsultation')"
            @click="pauseConsultation(appt.id)"
          />

          <!-- Reopen (today only, completed → revert to in_progress) -->
          <v-btn
            v-if="isToday && appt.status === 'completed' && !inProgressAppointment"
            color="secondary"
            variant="text"
            size="small"
            icon="mdi-restore"
            :loading="reopeningId === appt.id"
            :title="t('doctor.reopenConsultation')"
            @click="reopenConsultation(appt.id)"
          />

          <!-- Cancel (booked only, any date) -->
          <v-btn
            v-if="appt.status === 'booked'"
            color="error"
            variant="text"
            size="small"
            icon="mdi-cancel"
            :loading="cancellingId === appt.id"
            @click="openCancelDialog(appt)"
          />
        </div>
      </div>
    </v-card>
  </div>

  <!-- Cancel confirmation dialog -->
  <v-dialog v-model="cancelDialog" max-width="420">
    <v-card v-if="cancelTarget">
      <v-card-title class="pa-4 pb-2">
        <v-icon class="mr-2" color="error">mdi-cancel</v-icon>
        {{ t('doctor.cancelTitle') }}
      </v-card-title>
      <v-card-text class="pa-4 pt-1">
        <p>{{ t('doctor.cancelText', { patient: cancelTarget.patientName, time: formatTime(cancelTarget.slotStartAt) }) }}</p>
        <v-alert v-if="cancelError" type="error" density="compact" class="mt-3">{{ cancelError }}</v-alert>
      </v-card-text>
      <v-card-actions class="pa-4 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="cancelDialog = false">{{ t('admin.form.cancel') }}</v-btn>
        <v-btn color="error" variant="tonal" :loading="cancellingId !== null" @click="confirmCancel">
          {{ t('doctor.cancelConfirm') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Delay result dialog -->
  <v-dialog v-model="delayResultDialog" max-width="480">
    <v-card v-if="delayResult">
      <v-card-title class="pa-4 pb-2">
        <v-icon class="mr-2" :color="delayResult.additionalDelayMinutes > 0 ? 'warning' : 'success'">
          {{ delayResult.additionalDelayMinutes > 0 ? 'mdi-clock-alert' : 'mdi-check-circle' }}
        </v-icon>
        {{ t('doctor.consultationEnded') }}
      </v-card-title>
      <v-card-text class="pa-4 pt-2">
        <template v-if="delayResult.additionalDelayMinutes > 0">
          <v-alert type="warning" variant="tonal" density="compact" class="mb-3">
            {{ t('doctor.endedLate', { minutes: delayResult.additionalDelayMinutes }) }}
          </v-alert>
          <div class="text-body-2 font-weight-medium mb-2">
            {{ t('doctor.notifiedPatients', { count: delayResult.delayedAppointments.length }) }}
          </div>
          <v-list density="compact" class="rounded border">
            <v-list-item
              v-for="da in delayResult.delayedAppointments"
              :key="da.id"
              :subtitle="`${t('doctor.effectiveTime')}: ${formatTime(da.effectiveStart)}`"
            >
              <template #title>{{ da.patientName }}</template>
            </v-list-item>
          </v-list>
        </template>
        <template v-else>
          <v-alert type="success" variant="tonal" density="compact">
            {{ t('doctor.endedOnTime') }}
          </v-alert>
        </template>
      </v-card-text>
      <v-card-actions class="pa-4 pt-0">
        <v-spacer />
        <v-btn color="primary" variant="tonal" @click="delayResultDialog = false">
          {{ t('admin.form.confirm') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.border-warning {
  border-color: rgb(var(--v-theme-warning)) !important;
}
.opacity-70 {
  opacity: 0.7;
}
.opacity-50 {
  opacity: 0.5;
}
</style>
