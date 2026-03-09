<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  adminGetDoctors,
  getClinics,
  getSpecialties,
} from '@/services/adminService'
import DoctorFormDialog from '@/components/admin/DoctorFormDialog.vue'
import DoctorAvailabilityDialog from '@/components/admin/DoctorAvailabilityDialog.vue'
import { uploadUrl } from '@/utils/url'

const { t } = useI18n()

const doctors = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)
const search = ref('')
const loading = ref(false)
const activeStates = reactive({})

const clinics = ref([])
const specialties = ref([])

const dialog = ref(false)
const dialogMode = ref('create')
const selectedDoctor = ref(null)

const availabilityDialog = ref(false)
const availabilityDoctor = ref(null)

function openAvailability(doctor) {
  availabilityDoctor.value = doctor
  availabilityDialog.value = true
}

let searchTimer = null

const headers = [
  { title: t('admin.doctors.name'), key: 'name', sortable: false },
  { title: t('admin.doctors.clinic'), key: 'clinic', sortable: false },
  { title: t('admin.doctors.specialties'), key: 'specialties', sortable: false },
  { title: t('admin.doctors.insurance'), key: 'acceptsInsurance', sortable: false, align: 'center' },
  { title: t('admin.doctors.active'), key: 'isActive', sortable: false, align: 'center' },
  { title: t('admin.doctors.actions'), key: 'actions', sortable: false, align: 'center' },
]

async function fetchDoctors() {
  loading.value = true
  try {
    const result = await adminGetDoctors({ page: page.value, limit: limit.value, search: search.value || undefined })
    doctors.value = result.data
    total.value = result.total
    result.data.forEach(d => { activeStates[d.id] = d.isActive })
  } catch {
    // silently fail
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    fetchDoctors()
  }, 300)
}

watch(page, fetchDoctors)


function openCreate() {
  selectedDoctor.value = null
  dialogMode.value = 'create'
  dialog.value = true
}

function openEdit(doctor) {
  selectedDoctor.value = doctor
  dialogMode.value = 'edit'
  dialog.value = true
}

async function onSaved() {
  await fetchDoctors()
}

onMounted(async () => {
  const [c, s] = await Promise.all([getClinics(), getSpecialties()])
  clinics.value = c
  specialties.value = s
  await fetchDoctors()
})
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div class="d-flex align-center ga-3">
        <v-avatar color="primary" variant="tonal" rounded="lg" size="44">
          <v-icon size="24">mdi-doctor</v-icon>
        </v-avatar>
        <div>
          <h1 class="text-h5 font-weight-bold">{{ t('admin.doctors.title') }}</h1>
          <div class="text-body-2 text-medium-emphasis">{{ total }} {{ t('admin.doctors.total') }}</div>
        </div>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreate">
        {{ t('admin.doctors.addDoctor') }}
      </v-btn>
    </div>

    <v-text-field
      v-model="search"
      :placeholder="t('admin.doctors.searchPlaceholder')"
      prepend-inner-icon="mdi-magnify"
      variant="outlined"
      density="compact"
      clearable
      class="mb-4"
      style="max-width: 400px"
      @input="onSearchInput"
      @click:clear="() => { search = ''; page = 1; fetchDoctors() }"
    />

    <v-data-table-server
      :headers="headers"
      :items="doctors"
      :items-length="total"
      :page="page"
      :items-per-page="limit"
      :items-per-page-options="[
        { value: 10, title: '10' },
        { value: 20, title: '20' },
        { value: 50, title: '50' },
        { value: 100, title: '100' },
      ]"
      :loading="loading"
      @update:page="page = $event"
      @update:items-per-page="limit = $event; page = 1; fetchDoctors()"
    >
      <template #item.name="{ item }">
        <div class="d-flex align-center ga-2 py-1">
          <v-avatar :color="item.avatarPath ? undefined : 'primary'" variant="tonal" size="32">
            <v-img v-if="item.avatarPath" :src="uploadUrl(item.avatarPath)" cover />
            <v-icon v-else size="16">mdi-doctor</v-icon>
          </v-avatar>
          <span class="font-weight-medium">Dr. {{ item.firstName }} {{ item.lastName }}</span>
        </div>
      </template>

      <template #item.clinic="{ item }">
        <div class="d-flex align-center ga-1 text-body-2">
          <v-icon size="14" class="text-medium-emphasis">mdi-hospital-building</v-icon>
          {{ item.clinic?.name }}
        </div>
      </template>

      <template #item.specialties="{ item }">
        <v-chip
          v-for="s in item.specialties"
          :key="s.id"
          size="x-small"
          color="primary"
          variant="tonal"
          class="mr-1"
        >
          {{ s.name }}
        </v-chip>
      </template>

      <template #item.acceptsInsurance="{ item }">
        <v-icon
          :color="item.acceptsInsurance ? 'success' : 'error'"
          size="20"
        >
          {{ item.acceptsInsurance ? 'mdi-check-circle' : 'mdi-close-circle' }}
        </v-icon>
      </template>

      <template #item.isActive="{ item }">
        <v-chip
          :color="activeStates[item.id] ? 'success' : 'default'"
          :variant="activeStates[item.id] ? 'tonal' : 'outlined'"
          size="small"
        >
          <v-icon start size="14">{{ activeStates[item.id] ? 'mdi-check-circle' : 'mdi-circle-off-outline' }}</v-icon>
          {{ activeStates[item.id] ? t('admin.doctors.active') : t('admin.doctors.inactive') }}
        </v-chip>
      </template>

      <template #item.actions="{ item }">
        <v-btn size="small" icon variant="text" color="primary" @click="openEdit(item)">
          <v-icon size="18">mdi-pencil-outline</v-icon>
          <v-tooltip activator="parent" location="top">{{ t('admin.form.edit') }}</v-tooltip>
        </v-btn>
        <v-btn size="small" icon variant="text" color="secondary" @click="openAvailability(item)">
          <v-icon size="18">mdi-calendar-remove-outline</v-icon>
          <v-tooltip activator="parent" location="top">{{ t('admin.doctors.availability') }}</v-tooltip>
        </v-btn>
      </template>
    </v-data-table-server>

    <DoctorFormDialog
      v-model="dialog"
      :mode="dialogMode"
      :doctor="selectedDoctor"
      :clinics="clinics"
      :specialties="specialties"
      @saved="onSaved"
    />

    <DoctorAvailabilityDialog
      v-model="availabilityDialog"
      :doctor="availabilityDoctor"
    />
  </div>
</template>
