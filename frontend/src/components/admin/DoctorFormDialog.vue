<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  adminCreateDoctor,
  adminUpdateDoctor,
  adminGetMedicalServices,
  adminGetDoctorServices,
  adminAddDoctorService,
  adminUpdateDoctorService,
  adminDeleteDoctorService,
} from '@/services/adminService'

const props = defineProps({
  modelValue: Boolean,
  mode: { type: String, default: 'create' },
  doctor: { type: Object, default: null },
  clinics: { type: Array, default: () => [] },
  specialties: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'saved'])

const { t } = useI18n()

const activeTab = ref('info')
const saving = ref(false)
const errorMsg = ref('')
const leaveDialog = ref(false)

const emptyForm = () => ({
  firstName: '',
  lastName: '',
  bio: '',
  clinicId: null,
  specialtyIds: [],
  acceptsInsurance: false,
  isActive: true,
})

const form = ref(emptyForm())
const savedForm = ref(emptyForm())

const isDirty = computed(() => {
  const formDirty = JSON.stringify(form.value) !== JSON.stringify(savedForm.value)
  const servicesDirty = props.mode === 'create' && pendingServices.value.length > 0
  return formDirty || servicesDirty
})

// Services tab state
const allServices = ref([])
const doctorServices = ref([])
const pendingServices = ref([]) // used in create mode only
const newService = ref({ medicalServiceId: null, price: '', durationMinutes: 30 })
const addingService = ref(false)
const serviceError = ref('')

watch(
  () => props.modelValue,
  async (open) => {
    if (open) {
      activeTab.value = 'info'
      errorMsg.value = ''
      serviceError.value = ''
      leaveDialog.value = false
      pendingServices.value = []

      const initial = props.doctor
        ? {
            firstName: props.doctor.firstName ?? '',
            lastName: props.doctor.lastName ?? '',
            bio: props.doctor.bio ?? '',
            clinicId: props.doctor.clinic?.id ?? null,
            specialtyIds: props.doctor.specialties?.map(s => s.id) ?? [],
            acceptsInsurance: props.doctor.acceptsInsurance ?? false,
            isActive: props.doctor.isActive ?? true,
          }
        : emptyForm()

      form.value = JSON.parse(JSON.stringify(initial))
      savedForm.value = JSON.parse(JSON.stringify(initial))

      await loadServices()
    }
  }
)

function tryClose() {
  if (isDirty.value) {
    leaveDialog.value = true
  } else {
    emit('update:modelValue', false)
  }
}

function confirmLeave() {
  leaveDialog.value = false
  emit('update:modelValue', false)
}

async function loadServices() {
  try {
    allServices.value = await adminGetMedicalServices()
    doctorServices.value = props.mode === 'edit' && props.doctor
      ? await adminGetDoctorServices(props.doctor.id)
      : []
    newService.value = { medicalServiceId: null, price: '', durationMinutes: 30 }
  } catch {
    serviceError.value = t('admin.errors.loadFailed')
  }
}

// Services filtered by selected specialties, excluding already assigned/pending
const availableServices = computed(() => {
  const assignedIds = doctorServices.value.map(ds => ds.medicalService?.id)
  const pendingIds = pendingServices.value.map(s => s.medicalServiceId)
  const usedIds = new Set([...assignedIds, ...pendingIds])

  let pool = allServices.value.filter(s => !usedIds.has(s.id))

  if (form.value.specialtyIds.length > 0) {
    pool = pool.filter(s => !s.specialty || form.value.specialtyIds.includes(s.specialty.id))
  }

  return pool
})

function serviceItemTitle(s) {
  return s.specialty ? `${s.name} (${s.specialty.name})` : s.name
}

async function save() {
  saving.value = true
  errorMsg.value = ''
  try {
    const payload = {
      firstName: form.value.firstName,
      lastName: form.value.lastName,
      bio: form.value.bio || null,
      clinicId: form.value.clinicId,
      specialtyIds: form.value.specialtyIds,
      acceptsInsurance: form.value.acceptsInsurance,
      isActive: form.value.isActive,
    }

    if (props.mode === 'edit' && props.doctor) {
      await adminUpdateDoctor(props.doctor.id, payload)
    } else {
      const created = await adminCreateDoctor(payload)
      for (const svc of pendingServices.value) {
        await adminAddDoctorService(created.id, {
          medicalServiceId: svc.medicalServiceId,
          price: svc.price,
          durationMinutes: svc.durationMinutes,
        })
      }
    }

    savedForm.value = JSON.parse(JSON.stringify(form.value))
    emit('saved')
    emit('update:modelValue', false)
  } catch {
    errorMsg.value = t('admin.errors.saveFailed')
  } finally {
    saving.value = false
  }
}

async function addService() {
  if (!newService.value.medicalServiceId || !newService.value.price) return

  if (props.mode === 'create') {
    const svc = allServices.value.find(s => s.id === newService.value.medicalServiceId)
    pendingServices.value.push({
      medicalServiceId: newService.value.medicalServiceId,
      medicalService: svc,
      price: newService.value.price,
      durationMinutes: newService.value.durationMinutes,
    })
    newService.value = { medicalServiceId: null, price: '', durationMinutes: 30 }
    return
  }

  addingService.value = true
  serviceError.value = ''
  try {
    const created = await adminAddDoctorService(props.doctor.id, {
      medicalServiceId: newService.value.medicalServiceId,
      price: newService.value.price,
      durationMinutes: newService.value.durationMinutes,
    })
    doctorServices.value.push(created)
    newService.value = { medicalServiceId: null, price: '', durationMinutes: 30 }
    emit('saved')
  } catch (e) {
    serviceError.value = e?.response?.status === 409
      ? t('admin.services.errorDuplicate')
      : t('admin.errors.saveFailed')
  } finally {
    addingService.value = false
  }
}

function removePendingService(idx) {
  pendingServices.value.splice(idx, 1)
}

async function updateService(ds) {
  ds._saving = true
  try {
    await adminUpdateDoctorService(ds.id, { price: ds.price, durationMinutes: ds.durationMinutes })
    ds._editing = false
    emit('saved')
  } catch {
    serviceError.value = t('admin.errors.saveFailed')
  } finally {
    ds._saving = false
  }
}

async function deleteService(ds, index) {
  try {
    await adminDeleteDoctorService(ds.id)
    doctorServices.value.splice(index, 1)
    emit('saved')
  } catch (e) {
    serviceError.value = e?.response?.status === 409
      ? t('admin.services.errorConflict')
      : t('admin.errors.saveFailed')
  }
}
</script>

<template>
  <!-- Leave confirmation -->
  <v-dialog v-model="leaveDialog" max-width="380">
    <v-card>
      <v-card-title class="pa-4 pb-2">{{ t('admin.form.leaveTitle') }}</v-card-title>
      <v-card-text class="pa-4 pt-0 text-body-2 text-medium-emphasis">
        {{ t('admin.form.leaveText') }}
      </v-card-text>
      <v-card-actions class="pa-4 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="leaveDialog = false">{{ t('admin.form.stayHere') }}</v-btn>
        <v-btn color="error" variant="tonal" @click="confirmLeave">{{ t('admin.form.leaveConfirm') }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Main dialog -->
  <v-dialog
    :model-value="modelValue"
    @update:model-value="tryClose"
    max-width="640"
    scrollable
  >
    <v-card>
      <!-- Header with inline actions -->
      <div class="d-flex align-center justify-space-between px-4 pt-3 pb-1">
        <span class="text-h6 font-weight-bold">
          {{ mode === 'edit' ? t('admin.doctorForm.editTitle') : t('admin.doctorForm.createTitle') }}
        </span>
        <div class="d-flex align-center ga-2">
          <v-chip
            v-if="isDirty"
            color="warning"
            size="small"
            variant="tonal"
            prepend-icon="mdi-circle-medium"
          >
            {{ t('admin.form.unsavedChanges') }}
          </v-chip>
          <v-btn variant="text" size="small" @click="tryClose">{{ t('admin.form.cancel') }}</v-btn>
          <v-btn
            color="primary"
            size="small"
            :loading="saving"
            :disabled="!isDirty"
            @click="save"
          >
            {{ t('admin.form.save') }}
          </v-btn>
        </div>
      </div>

      <v-tabs v-model="activeTab" class="px-4">
        <v-tab value="info">{{ t('admin.doctorForm.tabInfo') }}</v-tab>
        <v-tab value="services">{{ t('admin.doctorForm.tabServices') }}</v-tab>
      </v-tabs>

      <v-divider />

      <!-- Scrollable body -->
      <v-card-text class="pa-0">
        <v-window v-model="activeTab">

          <!-- Info tab -->
          <v-window-item value="info">
            <div class="pa-4">
              <v-alert v-if="errorMsg" type="error" density="compact" class="mb-4">{{ errorMsg }}</v-alert>

              <v-row dense>
                <v-col cols="6">
                  <v-text-field
                    v-model="form.firstName"
                    :label="t('admin.doctorForm.firstName')"
                    variant="outlined"
                    density="compact"
                  />
                </v-col>
                <v-col cols="6">
                  <v-text-field
                    v-model="form.lastName"
                    :label="t('admin.doctorForm.lastName')"
                    variant="outlined"
                    density="compact"
                  />
                </v-col>
              </v-row>

              <v-textarea
                v-model="form.bio"
                :label="t('admin.doctorForm.bio')"
                variant="outlined"
                density="compact"
                rows="2"
                class="mb-3"
                auto-grow
              />

              <v-select
                v-model="form.clinicId"
                :items="clinics"
                item-value="id"
                item-title="name"
                :label="t('admin.doctorForm.clinic')"
                variant="outlined"
                density="compact"
                class="mb-3"
              />

              <v-select
                v-model="form.specialtyIds"
                :items="specialties"
                item-value="id"
                item-title="name"
                :label="t('admin.doctorForm.specialties')"
                variant="outlined"
                density="compact"
                multiple
                chips
                class="mb-3"
              />

              <v-switch
                v-model="form.acceptsInsurance"
                :label="t('admin.doctorForm.acceptsInsurance')"
                color="primary"
                density="compact"
                hide-details
                class="mb-2"
              />

              <v-switch
                v-model="form.isActive"
                :label="t('admin.doctorForm.isActive')"
                color="success"
                density="compact"
                hide-details
              />
            </div>
          </v-window-item>

          <!-- Services tab -->
          <v-window-item value="services">
            <div class="pa-4">
              <v-alert v-if="serviceError" type="error" density="compact" class="mb-4" closable @click:close="serviceError = ''">
                {{ serviceError }}
              </v-alert>

              <!-- Create mode: pending services list -->
              <template v-if="mode === 'create'">
                <div class="text-subtitle-2 mb-1">{{ t('admin.services.pendingTitle') }}</div>
                <p class="text-caption text-medium-emphasis mb-3">{{ t('admin.services.pendingHint') }}</p>

                <v-table density="compact" v-if="pendingServices.length > 0">
                  <thead>
                    <tr>
                      <th>{{ t('admin.services.service') }}</th>
                      <th>{{ t('admin.services.price') }}</th>
                      <th>{{ t('admin.services.duration') }}</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(svc, idx) in pendingServices" :key="idx">
                      <td>{{ svc.medicalService?.name }}</td>
                      <td>{{ svc.price }}</td>
                      <td>{{ svc.durationMinutes }}</td>
                      <td>
                        <v-btn size="x-small" icon variant="text" color="error" @click="removePendingService(idx)">
                          <v-icon>mdi-close</v-icon>
                        </v-btn>
                      </td>
                    </tr>
                  </tbody>
                </v-table>

                <div v-else class="text-body-2 text-medium-emphasis mb-4">{{ t('admin.services.noServicesPending') }}</div>
              </template>

              <!-- Edit mode: assigned services -->
              <template v-else>
                <div class="text-subtitle-2 mb-3">{{ t('admin.services.title') }}</div>

                <v-table density="compact" v-if="doctorServices.length > 0">
                  <thead>
                    <tr>
                      <th>{{ t('admin.services.service') }}</th>
                      <th>{{ t('admin.services.price') }}</th>
                      <th>{{ t('admin.services.duration') }}</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(ds, idx) in doctorServices" :key="ds.id">
                      <td>{{ ds.medicalService?.name }}</td>
                      <td>
                        <v-text-field
                          v-if="ds._editing"
                          v-model="ds.price"
                          density="compact"
                          variant="outlined"
                          hide-details
                          style="min-width: 80px"
                        />
                        <span v-else>{{ ds.price }}</span>
                      </td>
                      <td>
                        <v-text-field
                          v-if="ds._editing"
                          v-model.number="ds.durationMinutes"
                          density="compact"
                          variant="outlined"
                          type="number"
                          hide-details
                          style="min-width: 70px"
                        />
                        <span v-else>{{ ds.durationMinutes }}</span>
                      </td>
                      <td>
                        <div class="d-flex align-center ga-1">
                          <template v-if="ds._editing">
                            <v-btn size="x-small" icon color="primary" :loading="ds._saving" @click="updateService(ds)">
                              <v-icon>mdi-check</v-icon>
                            </v-btn>
                            <v-btn size="x-small" icon variant="text" @click="ds._editing = false">
                              <v-icon>mdi-close</v-icon>
                            </v-btn>
                          </template>
                          <template v-else>
                            <v-btn size="x-small" icon variant="text" @click="ds._editing = true">
                              <v-icon>mdi-pencil</v-icon>
                            </v-btn>
                            <v-btn size="x-small" icon variant="text" color="error" @click="deleteService(ds, idx)">
                              <v-icon>mdi-delete</v-icon>
                            </v-btn>
                          </template>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </v-table>

                <div v-else class="text-body-2 text-medium-emphasis mb-4">{{ t('admin.services.noServices') }}</div>
              </template>

              <v-divider class="my-4" />

              <!-- Add service row (shared by both modes) -->
              <div class="text-subtitle-2 mb-3">{{ t('admin.services.addService') }}</div>

              <v-row dense align="center">
                <v-col cols="5">
                  <v-select
                    v-model="newService.medicalServiceId"
                    :items="availableServices"
                    item-value="id"
                    :item-title="serviceItemTitle"
                    :label="t('admin.services.service')"
                    variant="outlined"
                    density="compact"
                    hide-details
                    :hint="form.specialtyIds.length > 0 ? t('admin.services.filteredBySpecialty') : ''"
                    persistent-hint
                  />
                </v-col>
                <v-col cols="3">
                  <v-text-field
                    v-model="newService.price"
                    :label="t('admin.services.price')"
                    variant="outlined"
                    density="compact"
                    hide-details
                  />
                </v-col>
                <v-col cols="2">
                  <v-text-field
                    v-model.number="newService.durationMinutes"
                    :label="t('admin.services.duration')"
                    variant="outlined"
                    density="compact"
                    type="number"
                    hide-details
                  />
                </v-col>
                <v-col cols="2">
                  <v-btn
                    color="primary"
                    :loading="addingService"
                    :disabled="!newService.medicalServiceId || !newService.price"
                    @click="addService"
                    block
                  >
                    {{ t('admin.services.save') }}
                  </v-btn>
                </v-col>
              </v-row>
            </div>
          </v-window-item>

        </v-window>
      </v-card-text>

    </v-card>
  </v-dialog>
</template>
