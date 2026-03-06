<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthService } from '@/services/authService'
import HealthTip from '@/components/ui/HealthTip.vue'
import { useAuthValidation } from '@/composables/useAuthValidation'

const emit = defineEmits(['success', 'error'])

const { t } = useI18n()
const { login } = useAuthService()

const formRef = ref(null)
const loginEmail = ref('')
const loginPassword = ref('')
const showPassword = ref(false)
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

  const { ok, deactivated } = await login(loginEmail.value, loginPassword.value)
  loading.value = false

  if (!ok) {
    formError.value = deactivated
      ? t('auth.login.accountDeactivated')
      : t('auth.login.invalidCredentials')
    return
  }

  emit('success')
}
</script>

<template>
  <v-form ref="formRef" validate-on="submit" @submit.prevent="submitLogin" class="auth-form">
    <v-text-field
      v-model="loginEmail"
      :label="t('auth.login.emailLabel')"
      type="email"
      autocomplete="username"
      variant="outlined"
      rounded="lg"
      density="comfortable"
      prepend-inner-icon="mdi-email-outline"
      :rules="emailRules"
      class="mb-1"
    />

    <v-text-field
      v-model="loginPassword"
      :label="t('auth.login.passwordLabel')"
      :type="showPassword ? 'text' : 'password'"
      autocomplete="current-password"
      variant="outlined"
      rounded="lg"
      density="comfortable"
      prepend-inner-icon="mdi-lock-outline"
      :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
      :rules="passwordRules"
      @click:append-inner="showPassword = !showPassword"
      class="mb-1"
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
        :label="t('auth.login.rememberMe')"
        color="primary"
        hide-details
        density="compact"
        class="mb-4"
      />

      <v-btn
        type="submit"
        color="primary"
        size="large"
        block
        rounded="lg"
        :loading="loading"
        elevation="0"
        class="submit-btn"
      >
        {{ t('auth.login.signIn') }}
        <v-icon end size="18">mdi-arrow-right</v-icon>
      </v-btn>

      <v-alert
        v-if="formError"
        type="error"
        variant="tonal"
        rounded="lg"
        density="compact"
        class="mt-3"
      >
        {{ formError }}
      </v-alert>
    </div>
  </v-form>
</template>

<style scoped>
.auth-form {
  display: flex;
  flex-direction: column;
  width: 100%;
}

.submit-btn {
  font-weight: 700;
  letter-spacing: 1.2px;
  height: 48px;
  background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%) !important;
  box-shadow: 0 4px 20px rgba(21, 101, 192, 0.28) !important;
  transition: box-shadow 0.2s ease, transform 0.2s ease !important;
}

.submit-btn:hover {
  box-shadow: 0 8px 28px rgba(21, 101, 192, 0.42) !important;
  transform: translateY(-1px);
}

.mobile-only-tip {
  display: none;
}

.form-lower {
  margin-top: 4px;
}

@media (max-width: 900px) {
  .auth-form {
    flex: 1 1 auto;
  }

  .auth-form > *:not(.form-lower) {
    flex: 0 0 auto;
  }

  .form-lower {
    margin-top: auto;
  }

  .mobile-only-tip {
    display: block;
    margin-bottom: 16px;
  }
}
</style>
