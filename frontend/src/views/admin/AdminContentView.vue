<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  adminGetMedicalServices, adminCreateMedicalService, adminDeleteMedicalService,
  adminGetSpecialties, adminCreateSpecialty, adminDeleteSpecialty,
  adminRegenerateSlots,
} from '@/services/adminService'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const auth = useAuthStore()

// ── Tab ──────────────────────────────────────────────────────────────────────
const tab = ref('services')

// ── Medical services ─────────────────────────────────────────────────────────
const services = ref([])
const specialties = ref([])
const servicesLoading = ref(false)
const serviceDialog = ref(false)
const serviceSaving = ref(false)
const serviceErrors = ref({})
const serviceForm = ref({ name: '', slug: '', specialtyId: null })

const deleteServiceConfirm = ref(false)
const serviceToDelete = ref(null)
const deletingService = ref(false)
const deleteServiceError = ref(null)

const serviceHeaders = [
  { title: t('admin.content.services.name'), key: 'name', sortable: false },
  { title: t('admin.content.services.specialty'), key: 'specialty', sortable: false },
  { title: t('admin.content.services.slug'), key: 'slug', sortable: false },
  { title: t('admin.content.services.actions'), key: 'actions', sortable: false, align: 'center' },
]

function slugify(text) {
  return text.toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]/g, '-')
    .replace(/-{2,}/g, '-')
    .replace(/^-+/, '').replace(/-+$/, '')
}

function onServiceNameInput(val) {
  serviceForm.value.slug = slugify(val)
}

function openCreateService() {
  serviceForm.value = { name: '', slug: '', specialtyId: null }
  serviceErrors.value = {}
  serviceDialog.value = true
}

async function saveService() {
  serviceSaving.value = true
  serviceErrors.value = {}
  const payload = {
    name: serviceForm.value.name,
    slug: serviceForm.value.slug,
    specialtyId: serviceForm.value.specialtyId,
  }
  try {
    await adminCreateMedicalService(payload)
    serviceDialog.value = false
    await loadServices()
  } catch (e) {
    serviceErrors.value = e.response?.data?.errors ?? {}
    if (!Object.keys(serviceErrors.value).length) {
      serviceErrors.value._general = t('admin.errors.saveFailed')
    }
  } finally {
    serviceSaving.value = false
  }
}

function openDeleteService(item) {
  serviceToDelete.value = item
  deleteServiceError.value = null
  deleteServiceConfirm.value = true
}

async function confirmDeleteService() {
  deletingService.value = true
  deleteServiceError.value = null
  try {
    await adminDeleteMedicalService(serviceToDelete.value.id)
    deleteServiceConfirm.value = false
    await loadServices()
  } catch (e) {
    deleteServiceError.value = e.response?.data?.error ?? t('admin.errors.deleteFailed')
  } finally {
    deletingService.value = false
  }
}

async function loadServices() {
  servicesLoading.value = true
  try {
    services.value = await adminGetMedicalServices()
  } finally {
    servicesLoading.value = false
  }
}

// ── Specialties ───────────────────────────────────────────────────────────────
const specialtiesLoading = ref(false)
const specialtyDialog = ref(false)
const specialtySaving = ref(false)
const specialtyErrors = ref({})
const specialtyForm = ref({ name: '', slug: '' })

const deleteSpecialtyConfirm = ref(false)
const specialtyToDelete = ref(null)
const deletingSpecialty = ref(false)
const deleteSpecialtyError = ref(null)

const specialtyHeaders = [
  { title: t('admin.content.specialties.name'), key: 'name', sortable: false },
  { title: t('admin.content.specialties.slug'), key: 'slug', sortable: false },
  { title: t('admin.content.specialties.actions'), key: 'actions', sortable: false, align: 'center' },
]

function onSpecialtyNameInput(val) {
  specialtyForm.value.slug = slugify(val)
}

function openCreateSpecialty() {
  specialtyForm.value = { name: '', slug: '' }
  specialtyErrors.value = {}
  specialtyDialog.value = true
}

async function saveSpecialty() {
  specialtySaving.value = true
  specialtyErrors.value = {}
  const payload = { name: specialtyForm.value.name, slug: specialtyForm.value.slug }
  try {
    await adminCreateSpecialty(payload)
    specialtyDialog.value = false
    await loadSpecialties()
  } catch (e) {
    specialtyErrors.value = e.response?.data?.errors ?? {}
    if (!Object.keys(specialtyErrors.value).length) {
      specialtyErrors.value._general = t('admin.errors.saveFailed')
    }
  } finally {
    specialtySaving.value = false
  }
}

function openDeleteSpecialty(item) {
  specialtyToDelete.value = item
  deleteSpecialtyError.value = null
  deleteSpecialtyConfirm.value = true
}

async function confirmDeleteSpecialty() {
  deletingSpecialty.value = true
  deleteSpecialtyError.value = null
  try {
    await adminDeleteSpecialty(specialtyToDelete.value.id)
    deleteSpecialtyConfirm.value = false
    await loadSpecialties()
  } catch (e) {
    deleteSpecialtyError.value = e.response?.data?.error ?? t('admin.errors.deleteFailed')
  } finally {
    deletingSpecialty.value = false
  }
}

async function loadSpecialties() {
  specialtiesLoading.value = true
  try {
    specialties.value = await adminGetSpecialties()
  } finally {
    specialtiesLoading.value = false
  }
}

// ── Tools ─────────────────────────────────────────────────────────────────────
const slotsLoading = ref(false)
const slotsResult = ref(null) // 'success' | 'error'

async function regenerateSlots() {
  slotsLoading.value = true
  slotsResult.value = null
  try {
    await adminRegenerateSlots()
    slotsResult.value = 'success'
  } catch {
    slotsResult.value = 'error'
  } finally {
    slotsLoading.value = false
  }
}

// ── Init ──────────────────────────────────────────────────────────────────────
onMounted(async () => {
  await Promise.all([loadServices(), loadSpecialties()])
})
</script>

<template>
  <!-- Service create dialog -->
  <v-dialog v-model="serviceDialog" max-width="480">
    <v-card>
      <v-card-title class="pa-4 pb-2">{{ t('admin.content.services.createTitle') }}</v-card-title>
      <v-card-text class="pa-4 pt-2">
        <v-text-field
          v-model="serviceForm.name"
          :label="t('admin.content.services.name')"
          variant="outlined"
          density="compact"
          :error-messages="serviceErrors.name"
          class="mb-3"
          @input="onServiceNameInput(serviceForm.name)"
        />
        <v-select
          v-model="serviceForm.specialtyId"
          :items="specialties"
          item-value="id"
          item-title="name"
          :label="t('admin.content.services.specialty')"
          variant="outlined"
          density="compact"
          clearable
          :error-messages="serviceErrors.specialtyId"
          class="mb-3"
        />
        <v-text-field
          v-model="serviceForm.slug"
          :label="t('admin.content.services.slug')"
          variant="outlined"
          density="compact"
          :hint="t('admin.content.services.slugHint')"
          persistent-hint
          :error-messages="serviceErrors.slug"
          readonly
        />
        <v-alert v-if="serviceErrors._general" type="error" density="compact" class="mt-3">
          {{ serviceErrors._general }}
        </v-alert>
      </v-card-text>
      <v-card-actions class="pa-4 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="serviceDialog = false">{{ t('admin.form.cancel') }}</v-btn>
        <v-btn color="primary" variant="tonal" :loading="serviceSaving" @click="saveService">
          {{ t('admin.form.save') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Service delete confirmation dialog -->
  <v-dialog v-model="deleteServiceConfirm" max-width="400">
    <v-card>
      <v-card-title class="pa-4 pb-2">{{ t('admin.content.services.deleteTitle') }}</v-card-title>
      <v-card-text class="pa-4 pt-2">
        <p>{{ t('admin.content.services.deleteConfirm', { name: serviceToDelete?.name }) }}</p>
        <v-alert v-if="deleteServiceError" type="error" density="compact" class="mt-3">
          {{ deleteServiceError }}
        </v-alert>
      </v-card-text>
      <v-card-actions class="pa-4 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="deleteServiceConfirm = false">{{ t('admin.form.cancel') }}</v-btn>
        <v-btn color="error" variant="tonal" :loading="deletingService" @click="confirmDeleteService">
          {{ t('admin.form.delete') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Specialty create dialog -->
  <v-dialog v-model="specialtyDialog" max-width="440">
    <v-card>
      <v-card-title class="pa-4 pb-2">{{ t('admin.content.specialties.createTitle') }}</v-card-title>
      <v-card-text class="pa-4 pt-2">
        <v-text-field
          v-model="specialtyForm.name"
          :label="t('admin.content.specialties.name')"
          variant="outlined"
          density="compact"
          :error-messages="specialtyErrors.name"
          class="mb-3"
          @input="onSpecialtyNameInput(specialtyForm.name)"
        />
        <v-text-field
          v-model="specialtyForm.slug"
          :label="t('admin.content.specialties.slug')"
          variant="outlined"
          density="compact"
          :hint="t('admin.content.specialties.slugHint')"
          persistent-hint
          :error-messages="specialtyErrors.slug"
          readonly
        />
        <v-alert v-if="specialtyErrors._general" type="error" density="compact" class="mt-3">
          {{ specialtyErrors._general }}
        </v-alert>
      </v-card-text>
      <v-card-actions class="pa-4 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="specialtyDialog = false">{{ t('admin.form.cancel') }}</v-btn>
        <v-btn color="primary" variant="tonal" :loading="specialtySaving" @click="saveSpecialty">
          {{ t('admin.form.save') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Specialty delete confirmation dialog -->
  <v-dialog v-model="deleteSpecialtyConfirm" max-width="400">
    <v-card>
      <v-card-title class="pa-4 pb-2">{{ t('admin.content.specialties.deleteTitle') }}</v-card-title>
      <v-card-text class="pa-4 pt-2">
        <p>{{ t('admin.content.specialties.deleteConfirm', { name: specialtyToDelete?.name }) }}</p>
        <v-alert v-if="deleteSpecialtyError" type="error" density="compact" class="mt-3">
          {{ deleteSpecialtyError }}
        </v-alert>
      </v-card-text>
      <v-card-actions class="pa-4 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="deleteSpecialtyConfirm = false">{{ t('admin.form.cancel') }}</v-btn>
        <v-btn color="error" variant="tonal" :loading="deletingSpecialty" @click="confirmDeleteSpecialty">
          {{ t('admin.form.delete') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <div>
    <!-- Page header -->
    <div class="d-flex align-center ga-3 mb-5">
      <v-avatar color="primary" variant="tonal" rounded="lg" size="44">
        <v-icon size="24">mdi-database-edit</v-icon>
      </v-avatar>
      <div>
        <h1 class="text-h5 font-weight-bold">{{ t('admin.nav.content') }}</h1>
      </div>
    </div>

    <v-tabs v-model="tab" color="primary" class="mb-4">
      <v-tab value="services">{{ t('admin.content.tabServices') }}</v-tab>
      <v-tab value="specialties">{{ t('admin.content.tabSpecialties') }}</v-tab>
      <v-tab value="tools">{{ t('admin.content.tabTools') }}</v-tab>
    </v-tabs>

    <!-- Medical Services tab -->
    <v-window v-model="tab">
      <v-window-item value="services">
        <div class="d-flex align-center justify-space-between mb-3">
          <div class="text-body-2 text-medium-emphasis">{{ services.length }} {{ t('admin.content.services.total') }}</div>
          <v-btn v-if="!auth.isClinicAdmin" color="primary" prepend-icon="mdi-plus" size="small" @click="openCreateService">
            {{ t('admin.content.services.addService') }}
          </v-btn>
        </div>
        <v-data-table
          :headers="serviceHeaders"
          :items="services"
          :loading="servicesLoading"
          density="compact"
          :items-per-page="20"
        >
          <template #item.specialty="{ item }">
            <span class="text-body-2">{{ item.specialty?.name ?? t('admin.content.services.noSpecialty') }}</span>
          </template>
          <template #item.slug="{ item }">
            <code class="text-caption">{{ item.slug }}</code>
          </template>
          <template #item.actions="{ item }">
            <v-btn v-if="!auth.isClinicAdmin" size="small" icon variant="text" color="error" @click="openDeleteService(item)">
              <v-icon size="18">mdi-delete</v-icon>
              <v-tooltip activator="parent" location="top">{{ t('admin.form.delete') }}</v-tooltip>
            </v-btn>
          </template>
        </v-data-table>
      </v-window-item>

      <!-- Specialties tab -->
      <v-window-item value="specialties">
        <div class="d-flex align-center justify-space-between mb-3">
          <div class="text-body-2 text-medium-emphasis">{{ specialties.length }} {{ t('admin.content.specialties.total') }}</div>
          <v-btn v-if="!auth.isClinicAdmin" color="primary" prepend-icon="mdi-plus" size="small" @click="openCreateSpecialty">
            {{ t('admin.content.specialties.addSpecialty') }}
          </v-btn>
        </div>
        <v-data-table
          :headers="specialtyHeaders"
          :items="specialties"
          :loading="specialtiesLoading"
          density="compact"
          :items-per-page="20"
        >
          <template #item.slug="{ item }">
            <code class="text-caption">{{ item.slug }}</code>
          </template>
          <template #item.actions="{ item }">
            <v-btn v-if="!auth.isClinicAdmin" size="small" icon variant="text" color="error" @click="openDeleteSpecialty(item)">
              <v-icon size="18">mdi-delete</v-icon>
              <v-tooltip activator="parent" location="top">{{ t('admin.form.delete') }}</v-tooltip>
            </v-btn>
          </template>
        </v-data-table>
      </v-window-item>

      <!-- Tools tab -->
      <v-window-item value="tools">
        <v-card variant="outlined" max-width="520">
          <v-card-text class="pa-5">
            <div class="d-flex align-center ga-3 mb-3">
              <v-avatar color="warning" variant="tonal" rounded="lg" size="40">
                <v-icon size="20">mdi-clock-time-four</v-icon>
              </v-avatar>
              <div>
                <div class="text-body-1 font-weight-medium">{{ t('admin.content.tools.slotsTitle') }}</div>
              </div>
            </div>
            <p class="text-body-2 text-medium-emphasis mb-4">{{ t('admin.content.tools.slotsText') }}</p>
            <v-btn
              color="warning"
              variant="tonal"
              prepend-icon="mdi-refresh"
              :loading="slotsLoading"
              @click="regenerateSlots"
            >
              {{ slotsLoading ? t('admin.content.tools.slotsRunning') : t('admin.content.tools.slotsBtn') }}
            </v-btn>
            <v-alert
              v-if="slotsResult"
              :type="slotsResult === 'success' ? 'success' : 'error'"
              density="compact"
              class="mt-4"
            >
              {{ slotsResult === 'success' ? t('admin.content.tools.slotsSuccess') : t('admin.content.tools.slotsError') }}
            </v-alert>
          </v-card-text>
        </v-card>
      </v-window-item>
    </v-window>
  </div>
</template>
