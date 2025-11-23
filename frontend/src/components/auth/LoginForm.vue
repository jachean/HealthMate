<script setup>
import { ref } from 'vue'
import { useAuthService } from '@/services/authService'
import BaseTextField from '@/components/ui/BaseTextField.vue'
import BasePasswordField from '@/components/ui/BasePasswordField.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import HealthTip from '@/components/ui/HealthTip.vue'
import { useAuthValidation } from '@/composables/useAuthValidation'

const emit = defineEmits(['success', 'error'])

const { login } = useAuthService()

const formRef = ref(null)
const loginEmail = ref('')
const loginPassword = ref('')
const remember = ref(false)
const loading = ref(false)
const formError = ref('')

const props = defineProps({
  currentTip: String,
  currentTipIndex: Number,
})

const { loginRules } = useAuthValidation()
const emailRules = loginRules.email
const passwordRules = loginRules.password

const submitLogin = async () => {
  formError.value = ''
  emit('error', '')
  loading.value = true

  const { valid } = await formRef.value.validate()
  if (!valid) {
    loading.value = false
    return
  }

  const ok = await login(loginEmail.value, loginPassword.value)
  loading.value = false

  if (!ok) {
    formError.value = 'Invalid email or password'
    return
  }

  emit('success')
}
</script>

<template>
  <v-form ref="formRef" @submit.prevent="submitLogin" class="auth-form">
    <BaseTextField
      v-model="loginEmail"
      label="Email"
      type="email"
      autocomplete="username"
      :rules="emailRules"
    />

    <BasePasswordField
      v-model="loginPassword"
      label="Password"
      autocomplete="current-password"
      :rules="passwordRules"
      field-class="field-last"
    />

    <div class="mobile-only-tip">
      <HealthTip
        :tip="currentTip"
        :index="currentTipIndex"
        mode="mobile"
      />
    </div>

    <div class="form-lower">
      <v-checkbox
        v-model="remember"
        label="Remember Me"
        hide-details
        class="mb-4"
      />
      <BaseButton type="submit" block :loading="loading">
        LOGIN
      </BaseButton>

      <p v-if="formError" class="form-error">
        {{ formError }}
      </p>
    </div>
  </v-form>
</template>

<style scoped src="@/styles/auth/login-form.css"></style>
