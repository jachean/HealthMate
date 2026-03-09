<script setup>
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  adminGetDoctorUnavailability,
  adminAddDoctorUnavailability,
  adminDeleteDoctorUnavailability,
} from '@/services/adminService'

const props = defineProps({
  modelValue: Boolean,
  doctor: { type: Object, default: null },
})
const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()

const periods = ref([])
const loading = ref(false)
const error = ref('')

const newFrom = ref('')
const newTo = ref('')
const newReason = ref('')
const saving = ref(false)
const saveError = ref('')
const fromMenu = ref(false)
const toMenu = ref(false)

const deleteDialog = ref(false)
const periodToDelete = ref(null)
const deleting = ref(false)
const deleteError = ref('')

const DAY_KEYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']
const workDayLabels = computed(() => {
  if (!props.doctor) return []
  return (props.doctor.workDays ?? []).map(n => t(`admin.availability.${DAY_KEYS[n - 1]}`))
})

watch(
  () => props.modelValue,
  async (open) => {
    if (open && props.doctor) {
      await load()
    }
  }
)

async function load() {
  loading.value = true
  error.value = ''
  try {
    periods.value = await adminGetDoctorUnavailability(props.doctor.id)
  } catch {
    error.value = t('admin.errors.loadFailed')
  } finally {
    loading.value = false
  }
}

async function addPeriod() {
  if (!newFrom.value || !newTo.value) return
  saving.value = true
  saveError.value = ''
  try {
    const created = await adminAddDoctorUnavailability(props.doctor.id, {
      dateFrom: newFrom.value,
      dateTo: newTo.value,
      reason: newReason.value || null,
    })
    periods.value.push(created)
    periods.value.sort((a, b) => a.dateFrom.localeCompare(b.dateFrom))
    newFrom.value = ''
    newTo.value = ''
    newReason.value = ''
  } catch (e) {
    const status = e?.response?.status
    saveError.value = status === 409
      ? t('admin.availability.overlapError')
      : t('admin.availability.saveError')
  } finally {
    saving.value = false
  }
}

function openDeleteDialog(period) {
  periodToDelete.value = period
  deleteError.value = ''
  deleteDialog.value = true
}

async function confirmDelete() {
  if (!periodToDelete.value) return
  deleting.value = true
  deleteError.value = ''
  try {
    await adminDeleteDoctorUnavailability(props.doctor.id, periodToDelete.value.id)
    periods.value = periods.value.filter(p => p.id !== periodToDelete.value.id)
    deleteDialog.value = false
    periodToDelete.value = null
  } catch {
    deleteError.value = t('admin.availability.deleteError')
  } finally {
    deleting.value = false
  }
}

function formatDate(iso) {
  const d = new Date(iso + 'T00:00:00')
  return d.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}

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

const doctorName = computed(() =>
  props.doctor ? `Dr. ${props.doctor.firstName} ${props.doctor.lastName}` : ''
)
</script>

<template>
  <!-- Delete confirmation dialog -->
  <v-dialog v-model="deleteDialog" max-width="420">
    <v-card>
      <v-card-title class="pa-4 pb-2">{{ t('admin.form.delete') }}</v-card-title>
      <v-card-text class="pa-4 pt-0">
        <p class="text-body-2 text-medium-emphasis">{{ t('admin.availability.deleteConfirm') }}</p>
        <v-alert v-if="deleteError" type="error" density="compact" class="mt-2">{{ deleteError }}</v-alert>
      </v-card-text>
      <v-card-actions class="pa-4 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="deleteDialog = false">{{ t('admin.form.cancel') }}</v-btn>
        <v-btn color="error" variant="tonal" :loading="deleting" @click="confirmDelete">
          {{ t('admin.form.delete') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Main dialog -->
  <v-dialog
    :model-value="modelValue"
    max-width="600"
    scrollable
    @update:model-value="emit('update:modelValue', $event)"
  >
    <v-card rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between pa-4 pb-3">
        <div>
          <div>{{ t('admin.availability.title') }}</div>
          <div class="text-body-2 text-medium-emphasis font-weight-regular">{{ doctorName }}</div>
        </div>
        <v-btn icon variant="text" size="small" @click="emit('update:modelValue', false)">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-4">
        <!-- Work schedule (read-only) -->
        <div class="mb-4">
          <div class="text-caption text-medium-emphasis text-uppercase font-weight-medium mb-2">
            {{ t('admin.availability.workSchedule') }}
          </div>
          <div class="d-flex align-center flex-wrap ga-2 mb-1">
            <v-chip
              v-for="label in workDayLabels"
              :key="label"
              size="small"
              color="primary"
              variant="tonal"
            >{{ label }}</v-chip>
          </div>
          <div v-if="doctor" class="text-body-2 text-medium-emphasis">
            {{ t('admin.availability.hours') }}: {{ doctor.startHour }}:00 – {{ doctor.endHour }}:00
          </div>
        </div>

        <v-divider class="mb-4" />

        <!-- Unavailability periods list -->
        <div class="mb-4">
          <div class="text-subtitle-2 font-weight-bold mb-2">
            <v-icon size="16" class="mr-1">mdi-calendar-remove-outline</v-icon>
            {{ t('admin.availability.blockDates') }}
          </div>

          <v-alert v-if="error" type="error" density="compact" class="mb-3">{{ error }}</v-alert>

          <div v-if="loading" class="d-flex justify-center py-4">
            <v-progress-circular indeterminate color="primary" size="28" />
          </div>

          <div v-else-if="periods.length" class="d-flex flex-column ga-2 mb-3">
            <v-card
              v-for="period in periods"
              :key="period.id"
              variant="outlined"
              rounded="lg"
            >
              <v-card-text class="pa-3 d-flex align-center justify-space-between">
                <div>
                  <div class="text-body-2 font-weight-medium">
                    {{ formatDate(period.dateFrom) }} – {{ formatDate(period.dateTo) }}
                  </div>
                  <div v-if="period.reason" class="text-caption text-medium-emphasis">{{ period.reason }}</div>
                </div>
                <v-btn icon variant="text" size="small" color="error" @click="openDeleteDialog(period)">
                  <v-icon size="16">mdi-delete-outline</v-icon>
                </v-btn>
              </v-card-text>
            </v-card>
          </div>
          <p v-else-if="!loading" class="text-body-2 text-medium-emphasis mb-3">
            {{ t('admin.availability.noPeriods') }}
          </p>
        </div>

        <!-- Add new period form -->
        <v-divider class="mb-4" />
        <div class="text-subtitle-2 font-weight-bold mb-3">
          <v-icon size="16" class="mr-1">mdi-plus</v-icon>
          {{ t('admin.availability.blockDates') }}
        </div>

        <v-row dense>
          <v-col cols="12" sm="5">
            <v-menu v-model="fromMenu" :close-on-content-click="false">
              <template #activator="{ props: menuProps }">
                <v-text-field
                  v-bind="menuProps"
                  :model-value="displayDate(newFrom)"
                  :label="t('admin.availability.dateFrom')"
                  prepend-inner-icon="mdi-calendar"
                  readonly
                  variant="outlined"
                  density="compact"
                  hide-details
                  clearable
                  @click:clear="newFrom = ''"
                />
              </template>
              <v-date-picker
                :model-value="newFrom ? new Date(newFrom + 'T00:00:00') : undefined"
                hide-header
                @update:model-value="(d) => { newFrom = toISO(d); fromMenu = false }"
              />
            </v-menu>
          </v-col>

          <v-col cols="12" sm="5">
            <v-menu v-model="toMenu" :close-on-content-click="false">
              <template #activator="{ props: menuProps }">
                <v-text-field
                  v-bind="menuProps"
                  :model-value="displayDate(newTo)"
                  :label="t('admin.availability.dateTo')"
                  prepend-inner-icon="mdi-calendar"
                  readonly
                  variant="outlined"
                  density="compact"
                  hide-details
                  clearable
                  @click:clear="newTo = ''"
                />
              </template>
              <v-date-picker
                :model-value="newTo ? new Date(newTo + 'T00:00:00') : undefined"
                :min="newFrom ? new Date(newFrom + 'T00:00:00') : undefined"
                hide-header
                @update:model-value="(d) => { newTo = toISO(d); toMenu = false }"
              />
            </v-menu>
          </v-col>

          <v-col cols="12" sm="2" class="d-flex align-start">
            <v-btn
              color="primary"
              :loading="saving"
              :disabled="!newFrom || !newTo"
              block
              @click="addPeriod"
            >
              {{ t('admin.availability.add') }}
            </v-btn>
          </v-col>

          <v-col cols="12">
            <v-text-field
              v-model="newReason"
              :label="t('admin.availability.reason')"
              variant="outlined"
              density="compact"
              hide-details
            />
          </v-col>
        </v-row>

        <v-alert v-if="saveError" type="error" density="compact" class="mt-3">{{ saveError }}</v-alert>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>
