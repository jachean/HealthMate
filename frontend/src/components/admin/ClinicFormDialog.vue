<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminCreateClinic, adminUpdateClinic } from '@/services/adminService'

const props = defineProps({
  modelValue: Boolean,
  clinic: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'saved'])

const { t } = useI18n()

const form = ref({ name: '', description: '', address: '', city: '' })
const saving = ref(false)
const errorMsg = ref('')

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
      } else {
        form.value = { name: '', description: '', address: '', city: '' }
      }
      errorMsg.value = ''
    }
  }
)

async function save() {
  saving.value = true
  errorMsg.value = ''
  try {
    if (props.clinic) {
      await adminUpdateClinic(props.clinic.id, form.value)
    } else {
      await adminCreateClinic(form.value)
    }
    emit('saved')
    emit('update:modelValue', false)
  } catch (e) {
    errorMsg.value = t('admin.errors.saveFailed')
  } finally {
    saving.value = false
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
