<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminGetAppointments, adminCancelAppointment, adminGetDoctors, getClinics } from '@/services/adminService'
import AppointmentDetailDialog from '@/components/admin/AppointmentDetailDialog.vue'
import ManualAppointmentDialog from '@/components/admin/ManualAppointmentDialog.vue'

const { t } = useI18n()

const appointments = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)
const loading = ref(false)
const errorMsg = ref('')

const doctors = ref([])
const clinics = ref([])

const filters = reactive({
  dateFrom: '',
  dateTo: '',
  doctorId: null,
  clinicId: null,
  status: null,
  patient: '',
})

let patientSearchTimer = null
function onPatientInput() {
  clearTimeout(patientSearchTimer)
  patientSearchTimer = setTimeout(() => {
    page.value = 1
    fetchAppointments()
  }, 300)
}

const statusOptions = computed(() => [
  { title: t('admin.appointments.statusBooked'), value: 'booked' },
  { title: t('admin.appointments.statusCancelled'), value: 'cancelled' },
])

const hasActiveFilters = computed(() =>
  filters.dateFrom || filters.dateTo || filters.doctorId || filters.clinicId || filters.status || filters.patient
)

const dateFromMenu = ref(false)
const dateToMenu = ref(false)

function displayDate(iso) {
  if (!iso) return ''
  const [y, m, d] = iso.split('-')
  return `${d}/${m}/${y}`
}

function toISO(date) {
  if (!date) return ''
  const d = date instanceof Date ? date : new Date(date)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const cancelDialog = ref(false)
const appointmentToCancel = ref(null)
const cancelling = ref(false)
const cancelError = ref('')

const detailDialog = ref(false)
const selectedAppointmentId = ref(null)

const manualDialog = ref(false)

function openDetail(appointment) {
  selectedAppointmentId.value = appointment.id
  detailDialog.value = true
}

function openManualCreate() {
  manualDialog.value = true
}

const headers = [
  { title: t('admin.appointments.dateTime'), key: 'startAt', sortable: false },
  { title: t('admin.appointments.patient'), key: 'patient', sortable: false },
  { title: t('admin.appointments.doctor'), key: 'doctor', sortable: false },
  { title: t('admin.appointments.service'), key: 'service', sortable: false },
  { title: t('admin.appointments.status'), key: 'status', sortable: false, align: 'center' },
  { title: t('admin.appointments.actions'), key: 'actions', sortable: false, align: 'center' },
]

watch(
  () => [filters.dateFrom, filters.dateTo, filters.doctorId, filters.clinicId, filters.status],
  () => { page.value = 1; fetchAppointments() }
)

watch(page, fetchAppointments)

async function fetchAppointments() {
  loading.value = true
  errorMsg.value = ''
  try {
    const result = await adminGetAppointments({
      page: page.value,
      limit: limit.value,
      dateFrom: filters.dateFrom || undefined,
      dateTo: filters.dateTo || undefined,
      doctorId: filters.doctorId || undefined,
      clinicId: filters.clinicId || undefined,
      status: filters.status || undefined,
      patient: filters.patient || undefined,
    })
    appointments.value = result.data
    total.value = result.total
  } catch {
    errorMsg.value = t('admin.errors.loadFailed')
  } finally {
    loading.value = false
  }
}

function clearFilters() {
  filters.dateFrom = ''
  filters.dateTo = ''
  filters.doctorId = null
  filters.clinicId = null
  filters.status = null
  filters.patient = ''
}

function openCancelDialog(appointment) {
  appointmentToCancel.value = appointment
  cancelError.value = ''
  cancelDialog.value = true
}

async function confirmCancel() {
  if (!appointmentToCancel.value) return
  cancelling.value = true
  cancelError.value = ''
  try {
    await adminCancelAppointment(appointmentToCancel.value.id)
    cancelDialog.value = false
    appointmentToCancel.value = null
    await fetchAppointments()
  } catch {
    cancelError.value = t('admin.appointments.errorCancelFailed')
  } finally {
    cancelling.value = false
  }
}

function formatDateTime(iso) {
  const d = new Date(iso)
  return d.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
    + ' ' + d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
}

onMounted(async () => {
  const [doctorResult, clinicList] = await Promise.all([
    adminGetDoctors({ limit: 500 }),
    getClinics(),
  ])
  doctors.value = doctorResult.data
  clinics.value = clinicList
  await fetchAppointments()
})
</script>

<template>
  <!-- Appointment detail dialog -->
  <AppointmentDetailDialog
    v-model="detailDialog"
    :appointment-id="selectedAppointmentId"
  />

  <!-- Manual appointment creation dialog -->
  <ManualAppointmentDialog
    v-model="manualDialog"
    @saved="fetchAppointments"
  />

  <!-- Cancel confirmation dialog -->
  <v-dialog v-model="cancelDialog" max-width="420">
    <v-card>
      <v-card-title class="pa-4 pb-2">{{ t('admin.appointments.cancelTitle') }}</v-card-title>
      <v-card-text class="pa-4 pt-0">
        <p class="text-body-2 text-medium-emphasis mb-3">{{ t('admin.appointments.cancelText') }}</p>
        <template v-if="appointmentToCancel">
          <div class="text-body-2">
            <strong>{{ appointmentToCancel.patient.name }}</strong>
            &mdash; {{ appointmentToCancel.doctor.name }}
          </div>
          <div class="text-caption text-medium-emphasis">{{ formatDateTime(appointmentToCancel.startAt) }}</div>
        </template>
        <v-alert v-if="cancelError" type="error" density="compact" class="mt-3">{{ cancelError }}</v-alert>
      </v-card-text>
      <v-card-actions class="pa-4 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="cancelDialog = false">{{ t('admin.form.cancel') }}</v-btn>
        <v-btn color="error" variant="tonal" :loading="cancelling" @click="confirmCancel">
          {{ t('admin.appointments.cancelConfirm') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <div>
    <!-- Page header -->
    <div class="d-flex align-center justify-space-between mb-5">
      <div class="d-flex align-center ga-3">
        <v-avatar color="primary" variant="tonal" rounded="lg" size="44">
          <v-icon size="24">mdi-calendar-clock</v-icon>
        </v-avatar>
        <div>
          <h1 class="text-h5 font-weight-bold">{{ t('admin.appointments.title') }}</h1>
          <div class="text-body-2 text-medium-emphasis">{{ total }} {{ t('admin.appointments.total') }}</div>
        </div>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openManualCreate">
        {{ t('admin.appointments.create.title') }}
      </v-btn>
    </div>

    <!-- Filters -->
    <v-card variant="outlined" class="mb-4 pa-3">
      <v-row dense align="center">
        <v-col cols="12" sm="2">
          <v-text-field
            v-model="filters.patient"
            :label="t('admin.appointments.patientSearch')"
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            density="compact"
            hide-details
            clearable
            @input="onPatientInput"
            @click:clear="() => { filters.patient = ''; page = 1; fetchAppointments() }"
          />
        </v-col>
        <v-col cols="12" sm="2">
          <v-menu v-model="dateFromMenu" :close-on-content-click="false">
            <template #activator="{ props: menuProps }">
              <v-text-field
                v-bind="menuProps"
                :model-value="displayDate(filters.dateFrom)"
                :label="t('admin.appointments.dateFrom')"
                prepend-inner-icon="mdi-calendar"
                readonly
                variant="outlined"
                density="compact"
                hide-details
                clearable
                @click:clear="filters.dateFrom = ''"
              />
            </template>
            <v-date-picker
              :model-value="filters.dateFrom ? new Date(filters.dateFrom + 'T00:00:00') : undefined"
              hide-header
              @update:model-value="(d) => { filters.dateFrom = toISO(d); dateFromMenu = false }"
            />
          </v-menu>
        </v-col>
        <v-col cols="12" sm="2">
          <v-menu v-model="dateToMenu" :close-on-content-click="false">
            <template #activator="{ props: menuProps }">
              <v-text-field
                v-bind="menuProps"
                :model-value="displayDate(filters.dateTo)"
                :label="t('admin.appointments.dateTo')"
                prepend-inner-icon="mdi-calendar"
                readonly
                variant="outlined"
                density="compact"
                hide-details
                clearable
                @click:clear="filters.dateTo = ''"
              />
            </template>
            <v-date-picker
              :model-value="filters.dateTo ? new Date(filters.dateTo + 'T00:00:00') : undefined"
              hide-header
              @update:model-value="(d) => { filters.dateTo = toISO(d); dateToMenu = false }"
            />
          </v-menu>
        </v-col>
        <v-col cols="12" sm="2">
          <v-autocomplete
            v-model="filters.doctorId"
            :items="doctors"
            item-value="id"
            :item-title="d => `Dr. ${d.firstName} ${d.lastName}`"
            :label="t('admin.appointments.allDoctors')"
            variant="outlined"
            density="compact"
            hide-details
            clearable
            :custom-filter="(_, query, item) => {
              const d = item.raw
              const name = `${d.firstName} ${d.lastName}`.toLowerCase()
              const clinic = (d.clinic?.name ?? '').toLowerCase()
              const q = query.toLowerCase()
              return name.includes(q) || clinic.includes(q)
            }"
          >
            <template #item="{ item, props: itemProps }">
              <v-list-item v-bind="itemProps" :subtitle="item.raw.clinic?.name">
                <template #title>
                  <span>Dr. {{ item.raw.firstName }} {{ item.raw.lastName }}</span>
                  <v-chip
                    v-if="!item.raw.isActive"
                    size="x-small"
                    color="warning"
                    variant="tonal"
                    class="ml-2"
                  >{{ t('admin.doctors.inactive') }}</v-chip>
                </template>
              </v-list-item>
            </template>
          </v-autocomplete>
        </v-col>
        <v-col cols="12" sm="2">
          <v-select
            v-model="filters.clinicId"
            :items="clinics"
            item-value="id"
            item-title="name"
            :label="t('admin.appointments.allClinics')"
            variant="outlined"
            density="compact"
            hide-details
            clearable
          />
        </v-col>
        <v-col cols="12" sm="1">
          <v-select
            v-model="filters.status"
            :items="statusOptions"
            :label="t('admin.appointments.allStatuses')"
            variant="outlined"
            density="compact"
            hide-details
            clearable
          />
        </v-col>
        <v-col cols="12" sm="1" class="d-flex justify-end">
          <v-btn
            variant="text"
            size="small"
            :disabled="!hasActiveFilters"
            @click="clearFilters"
          >
            {{ t('admin.appointments.clearFilters') }}
          </v-btn>
        </v-col>
      </v-row>
    </v-card>

    <v-alert v-if="errorMsg" type="error" density="compact" class="mb-4">{{ errorMsg }}</v-alert>

    <!-- Data table -->
    <v-data-table-server
      :headers="headers"
      :items="appointments"
      :items-length="total"
      :page="page"
      :items-per-page="limit"
      :items-per-page-options="[
        { value: 10, title: '10' },
        { value: 20, title: '20' },
        { value: 50, title: '50' },
      ]"
      :loading="loading"
      :no-data-text="t('admin.appointments.noAppointments')"
      @update:page="page = $event"
      @update:items-per-page="limit = $event; page = 1; fetchAppointments()"
    >
      <template #item.startAt="{ item }">
        <div class="text-body-2 font-weight-medium">{{ formatDateTime(item.startAt) }}</div>
      </template>

      <template #item.patient="{ item }">
        <div class="text-body-2 font-weight-medium">{{ item.patient.name }}</div>
        <div class="text-caption text-medium-emphasis">{{ item.patient.email }}</div>
      </template>

      <template #item.doctor="{ item }">
        <div class="text-body-2 font-weight-medium">Dr. {{ item.doctor.name }}</div>
        <div class="text-caption text-medium-emphasis d-flex align-center ga-1">
          <v-icon size="12">mdi-hospital-building</v-icon>
          {{ item.doctor.clinic }}
        </div>
      </template>

      <template #item.service="{ item }">
        <div class="text-body-2">{{ item.service.name }}</div>
        <div class="text-caption text-medium-emphasis">{{ item.service.price }} RON</div>
      </template>

      <template #item.status="{ item }">
        <v-chip
          :color="item.status === 'booked' ? 'success' : 'default'"
          :variant="item.status === 'booked' ? 'tonal' : 'outlined'"
          size="small"
        >
          {{ item.status === 'booked' ? t('admin.appointments.statusBooked') : t('admin.appointments.statusCancelled') }}
        </v-chip>
      </template>

      <template #item.actions="{ item }">
        <v-btn size="small" icon variant="text" color="primary" @click="openDetail(item)">
          <v-icon size="18">mdi-eye-outline</v-icon>
          <v-tooltip activator="parent" location="top">{{ t('admin.appointments.detail.title') }}</v-tooltip>
        </v-btn>
        <v-btn
          size="small"
          icon
          variant="text"
          color="error"
          :disabled="item.status === 'cancelled'"
          @click="openCancelDialog(item)"
        >
          <v-icon size="18">mdi-cancel</v-icon>
          <v-tooltip activator="parent" location="top">{{ t('admin.appointments.cancelTitle') }}</v-tooltip>
        </v-btn>
      </template>
    </v-data-table-server>
  </div>
</template>
