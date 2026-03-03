<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminGetClinics } from '@/services/adminService'
import ClinicFormDialog from '@/components/admin/ClinicFormDialog.vue'

const { t } = useI18n()

const clinics = ref([])
const loading = ref(false)

const dialog = ref(false)
const selectedClinic = ref(null)

const headers = [
  { title: t('admin.clinics.name'), key: 'name', sortable: true },
  { title: t('admin.clinics.city'), key: 'city', sortable: true },
  { title: t('admin.clinics.address'), key: 'address', sortable: false },
  { title: t('admin.clinics.description'), key: 'description', sortable: false },
  { title: t('admin.clinics.actions'), key: 'actions', sortable: false, align: 'center' },
]

async function fetchClinics() {
  loading.value = true
  try {
    clinics.value = await adminGetClinics()
  } catch {
    // silently fail
  } finally {
    loading.value = false
  }
}

function openCreate() {
  selectedClinic.value = null
  dialog.value = true
}

function openEdit(clinic) {
  selectedClinic.value = clinic
  dialog.value = true
}

async function onSaved() {
  await fetchClinics()
}

onMounted(fetchClinics)
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div class="d-flex align-center ga-3">
        <v-avatar color="secondary" variant="tonal" rounded="lg" size="44">
          <v-icon size="24">mdi-hospital-building</v-icon>
        </v-avatar>
        <div>
          <h1 class="text-h5 font-weight-bold">{{ t('admin.clinics.title') }}</h1>
          <div class="text-body-2 text-medium-emphasis">{{ clinics.length }} {{ t('admin.clinics.total') }}</div>
        </div>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreate">
        {{ t('admin.clinics.addClinic') }}
      </v-btn>
    </div>

    <v-data-table
      :headers="headers"
      :items="clinics"
      :loading="loading"
      :items-per-page="20"
      :items-per-page-options="[
        { value: 10, title: '10' },
        { value: 20, title: '20' },
        { value: 50, title: '50' },
        { value: 100, title: '100' },
      ]"
    >
      <template #item.name="{ item }">
        <div class="d-flex align-center ga-2 py-1">
          <v-avatar color="secondary" variant="tonal" size="32">
            <v-icon size="16">mdi-hospital-building</v-icon>
          </v-avatar>
          <span class="font-weight-medium">{{ item.name }}</span>
        </div>
      </template>

      <template #item.city="{ item }">
        <div class="d-flex align-center ga-1 text-body-2">
          <v-icon size="14" class="text-medium-emphasis">mdi-map-marker</v-icon>
          {{ item.city }}
        </div>
      </template>

      <template #item.description="{ item }">
        <span class="text-body-2 text-medium-emphasis text-truncate" style="max-width: 220px; display: inline-block">
          {{ item.description || '—' }}
        </span>
      </template>

      <template #item.actions="{ item }">
        <v-btn size="small" icon variant="text" color="primary" @click="openEdit(item)">
          <v-icon size="18">mdi-pencil-outline</v-icon>
          <v-tooltip activator="parent" location="top">{{ t('admin.form.edit') }}</v-tooltip>
        </v-btn>
      </template>
    </v-data-table>

    <ClinicFormDialog
      v-model="dialog"
      :clinic="selectedClinic"
      @saved="onSaved"
    />
  </div>
</template>
