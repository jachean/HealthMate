<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminCreateClinic, adminUpdateClinic, adminUploadFile } from '@/services/adminService'
import { uploadUrl } from '@/utils/url'

const props = defineProps({
  modelValue: Boolean,
  clinic: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'saved'])

const { t } = useI18n()

const form = ref({ name: '', description: '', address: '', city: '' })
const saving = ref(false)
const errorMsg = ref('')
const logoPath = ref(null)
const uploadingLogo = ref(false)
const logoInputRef = ref(null)

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      if (props.clinic) {
        form.value = {
          name: props.clinic.name ?? '',
          description: props.clinic.description ?? '',
          address: props.clinic.address ?? '',
          city: props.clinic.city ?? '',
        }
        logoPath.value = props.clinic.logoPath ?? null
      } else {
        form.value = { name: '', description: '', address: '', city: '' }
        logoPath.value = null
      }
      errorMsg.value = ''
    }
  }
)

async function save() {
  saving.value = true
  errorMsg.value = ''
  try {
    const payload = { ...form.value, logo: logoPath.value }
    if (props.clinic) {
      await adminUpdateClinic(props.clinic.id, payload)
    } else {
      await adminCreateClinic(payload)
    }
    emit('saved')
    emit('update:modelValue', false)
  } catch (e) {
    errorMsg.value = t('admin.errors.saveFailed')
  } finally {
    saving.value = false
  }
}

async function onLogoPick(e) {
  const file = e.target.files[0]
  if (!file) return
  uploadingLogo.value = true
  try {
    logoPath.value = await adminUploadFile(file, 'clinics')
  } catch {
    errorMsg.value = t('admin.errors.uploadFailed')
  } finally {
    uploadingLogo.value = false
    e.target.value = ''
  }
}
</script>

<template>
  <v-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" max-width="520">
    <v-card>
      <v-card-title class="pa-4 pb-2">
        {{ clinic ? t('admin.clinicForm.editTitle') : t('admin.clinicForm.createTitle') }}
      </v-card-title>

      <v-card-text class="pa-4 pt-2">
        <v-alert v-if="errorMsg" type="error" density="compact" class="mb-4">{{ errorMsg }}</v-alert>

        <!-- Logo upload -->
        <div class="d-flex justify-center mb-4">
          <div class="logo-upload-wrap" @click="logoInputRef.click()">
            <v-avatar size="72" :color="logoPath ? undefined : 'primary'" variant="tonal" rounded="xl" class="logo-upload-circle">
              <v-img v-if="logoPath" :src="uploadUrl(logoPath)" cover />
              <v-icon v-else size="32">mdi-hospital-building</v-icon>
              <div class="logo-overlay">
                <v-progress-circular v-if="uploadingLogo" indeterminate color="white" size="24" />
                <v-icon v-else color="white" size="20">mdi-camera</v-icon>
              </div>
            </v-avatar>
            <input ref="logoInputRef" type="file" accept="image/jpeg,image/png,image/webp" style="display:none" @change="onLogoPick" />
          </div>
        </div>

        <v-text-field
          v-model="form.name"
          :label="t('admin.clinicForm.name')"
          variant="outlined"
          density="compact"
          class="mb-3"
        />
        <v-text-field
          v-model="form.description"
          :label="t('admin.clinicForm.description')"
          variant="outlined"
          density="compact"
          class="mb-3"
        />
        <v-text-field
          v-model="form.address"
          :label="t('admin.clinicForm.address')"
          variant="outlined"
          density="compact"
          class="mb-3"
        />
        <v-text-field
          v-model="form.city"
          :label="t('admin.clinicForm.city')"
          variant="outlined"
          density="compact"
        />
      </v-card-text>

      <v-card-actions class="pa-4 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="emit('update:modelValue', false)">{{ t('admin.form.cancel') }}</v-btn>
        <v-btn color="primary" :loading="saving" @click="save">{{ t('admin.form.save') }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.logo-upload-wrap {
  position: relative;
  cursor: pointer;
}

.logo-upload-circle {
  transition: filter 0.2s;
}

.logo-upload-wrap:hover .logo-upload-circle {
  filter: brightness(0.8);
}

.logo-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.35);
  border-radius: 12px;
  opacity: 0;
  transition: opacity 0.2s;
}

.logo-upload-wrap:hover .logo-overlay {
  opacity: 1;
}
</style>
