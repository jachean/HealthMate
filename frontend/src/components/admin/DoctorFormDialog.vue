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
  adminUploadFile,
} from '@/services/adminService'
import { uploadUrl } from '@/utils/url'

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
const avatarPath = ref(null)
const uploadingAvatar = ref(false)
const avatarInputRef = ref(null)

const allDays = computed(() => [
  { value: 1, label: t('admin.doctorForm.mon') },
  { value: 2, label: t('admin.doctorForm.tue') },
  { value: 3, label: t('admin.doctorForm.wed') },
  { value: 4, label: t('admin.doctorForm.thu') },
  { value: 5, label: t('admin.doctorForm.fri') },
  { value: 6, label: t('admin.doctorForm.sat') },
  { value: 7, label: t('admin.doctorForm.sun') },
])

const hourOptions = Array.from({ length: 25 }, (_, i) => ({
  value: i,
  title: `${String(i).padStart(2, '0')}:00`,
}))

function toggleDay(day) {
  const days = form.value.workDays
  const idx = days.indexOf(day)
  form.value.workDays = idx >= 0
    ? days.filter(d => d !== day)
    : [...days, day].sort((a, b) => a - b)
}

const emptyForm = () => ({
  firstName: '',
  lastName: '',
  bio: '',
  clinicId: null,
  specialtyIds: [],
  acceptsInsurance: false,
  isActive: true,
  workDays: [1, 2, 3, 4, 5],
  startHour: 9,
  endHour: 17,
})

const form = ref(emptyForm())
const savedForm = ref(emptyForm())
const savedAvatarPath = ref(null)

const isDirty = computed(() => {
  const formDirty = JSON.stringify(form.value) !== JSON.stringify(savedForm.value)
  const avatarDirty = avatarPath.value !== savedAvatarPath.value
  const servicesDirty = props.mode === 'create' && pendingServices.value.length > 0
  return formDirty || avatarDirty || servicesDirty
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
      avatarPath.value = props.doctor?.avatarPath ?? null
      savedAvatarPath.value = props.doctor?.avatarPath ?? null

      const initial = props.doctor
        ? {
            firstName: props.doctor.firstName ?? '',
            lastName: props.doctor.lastName ?? '',
            bio: props.doctor.bio ?? '',
            clinicId: props.doctor.clinic?.id ?? null,
            specialtyIds: props.doctor.specialties?.map(s => s.id) ?? [],
            acceptsInsurance: props.doctor.acceptsInsurance ?? false,
            isActive: props.doctor.isActive ?? true,
            workDays: props.doctor.workDays ?? [1, 2, 3, 4, 5],
            startHour: props.doctor.startHour ?? 9,
            endHour: props.doctor.endHour ?? 17,
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

async function onAvatarPick(e) {
  const file = e.target.files[0]
  if (!file) return
  uploadingAvatar.value = true
  try {
    avatarPath.value = await adminUploadFile(file, 'doctors')
  } catch {
    errorMsg.value = t('admin.errors.uploadFailed')
  } finally {
    uploadingAvatar.value = false
    e.target.value = ''
  }
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
      workDays: form.value.workDays,
      startHour: form.value.startHour,
      endHour: form.value.endHour,
      avatar: avatarPath.value,
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
    savedAvatarPath.value = avatarPath.value
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

              <!-- Avatar upload -->
              <div class="d-flex justify-center mb-4">
                <div class="avatar-upload-wrap" @click="avatarInputRef.click()">
                  <v-avatar size="72" :color="avatarPath ? undefined : 'primary'" class="avatar-upload-circle">
                    <v-img v-if="avatarPath" :src="uploadUrl(avatarPath)" cover />
                    <span v-else class="text-h5 text-white font-weight-bold">
                      {{ (form.firstName?.[0] ?? '') + (form.lastName?.[0] ?? '') || '?' }}
                    </span>
                    <div class="avatar-overlay">
                      <v-progress-circular v-if="uploadingAvatar" indeterminate color="white" size="24" />
                      <v-icon v-else color="white" size="20">mdi-camera</v-icon>
                    </div>
                  </v-avatar>
                  <input ref="avatarInputRef" type="file" accept="image/jpeg,image/png,image/webp" style="display:none" @change="onAvatarPick" />
                </div>
              </div>

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

              <!-- Working hours -->
              <v-divider class="mb-4" />
              <div class="text-subtitle-2 mb-3">{{ t('admin.doctorForm.workingHours') }}</div>

              <div class="text-caption text-medium-emphasis mb-2">{{ t('admin.doctorForm.workDays') }}</div>
              <div class="d-flex flex-wrap ga-1 mb-4">
                <v-btn
                  v-for="day in allDays"
                  :key="day.value"
                  :color="form.workDays.includes(day.value) ? 'primary' : undefined"
                  :variant="form.workDays.includes(day.value) ? 'flat' : 'outlined'"
                  size="small"
                  rounded="lg"
                  @click="toggleDay(day.value)"
                >
                  {{ day.label }}
                </v-btn>
              </div>

              <v-row dense class="mb-2">
                <v-col cols="6">
                  <v-select
                    v-model="form.startHour"
                    :items="hourOptions"
                    item-value="value"
                    item-title="title"
                    :label="t('admin.doctorForm.startHour')"
                    variant="outlined"
                    density="compact"
                    hide-details
                  />
                </v-col>
                <v-col cols="6">
                  <v-select
                    v-model="form.endHour"
                    :items="hourOptions"
                    item-value="value"
                    item-title="title"
                    :label="t('admin.doctorForm.endHour')"
                    variant="outlined"
                    density="compact"
                    hide-details
                  />
                </v-col>
              </v-row>

              <v-divider class="mb-3" />

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
                      <th scope="col">{{ t('admin.services.service') }}</th>
                      <th scope="col">{{ t('admin.services.price') }}</th>
                      <th scope="col">{{ t('admin.services.duration') }}</th>
                      <th scope="col"></th>
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
                      <th scope="col">{{ t('admin.services.service') }}</th>
                      <th scope="col">{{ t('admin.services.price') }}</th>
                      <th scope="col">{{ t('admin.services.duration') }}</th>
                      <th scope="col"></th>
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

<style scoped>
.avatar-upload-wrap {
  position: relative;
  cursor: pointer;
}

.avatar-upload-circle {
  transition: filter 0.2s;
}

.avatar-upload-wrap:hover .avatar-upload-circle {
  filter: brightness(0.8);
}

.avatar-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.35);
  border-radius: 50%;
  opacity: 0;
  transition: opacity 0.2s;
}

.avatar-upload-wrap:hover .avatar-overlay {
  opacity: 1;
}
</style>
