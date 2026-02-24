<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthValidation } from '@/composables/useAuthValidation'
import { useAuthService } from '@/services/authService'

const emit = defineEmits(['success', 'error'])

const formRef = ref(null)

const firstName = ref('')
const lastName = ref('')
const email = ref('')
const username = ref('')
const password = ref('')
const confirmPassword = ref('')
const loading = ref(false)

const backendErrors = ref({})

const { t } = useI18n()
const { registerRules } = useAuthValidation(password)
const { register } = useAuthService()

const firstNameRules = registerRules.firstName
const lastNameRules = registerRules.lastName
const emailRules = registerRules.email
const usernameRules = registerRules.username
const passwordRules = registerRules.password
const confirmRules = registerRules.confirmPassword

const submit = async () => {
  loading.value = true
  backendErrors.value = {}
  emit('error', '')

  const { valid } = await formRef.value.validate()
  if (!valid) {
    loading.value = false
    return
  }

  const payload = {
    firstName: firstName.value,
    lastName: lastName.value,
    email: email.value,
    username: username.value,
    password: password.value,
    confirmPassword: confirmPassword.value,
  }

  const result = await register(payload)
  loading.value = false

  if (!result.ok) {
    if (result.validationErrors) {
      backendErrors.value = result.validationErrors
    } else {
      emit('error', t('auth.register.error'))
    }
    return
  }

  emit('success')
}
</script>

<template>
  <v-form ref="formRef" validate-on="submit" @submit.prevent="submit" class="auth-form">
    <div class="name-row">
      <v-text-field
        v-model="firstName"
        :label="t('auth.register.firstName')"
        variant="outlined"
        rounded="lg"
        density="comfortable"
        :rules="firstNameRules"
        :error-messages="backendErrors.firstName"
      />
      <v-text-field
        v-model="lastName"
        :label="t('auth.register.lastName')"
        variant="outlined"
        rounded="lg"
        density="comfortable"
        :rules="lastNameRules"
        :error-messages="backendErrors.lastName"
      />
    </div>

    <v-text-field
      v-model="email"
      type="email"
      :label="t('auth.register.email')"
      autocomplete="email"
      variant="outlined"
      rounded="lg"
      density="comfortable"
      prepend-inner-icon="mdi-email-outline"
      :rules="emailRules"
      :error-messages="backendErrors.email"
      class="mb-1"
    />

    <v-text-field
      v-model="username"
      :label="t('auth.register.username')"
      variant="outlined"
      rounded="lg"
      density="comfortable"
      prepend-inner-icon="mdi-at"
      :rules="usernameRules"
      :error-messages="backendErrors.username"
      class="mb-1"
    />

    <v-text-field
      v-model="password"
      type="password"
      :label="t('auth.register.password')"
      autocomplete="new-password"
      variant="outlined"
      rounded="lg"
      density="comfortable"
      prepend-inner-icon="mdi-lock-outline"
      :rules="passwordRules"
      :error-messages="backendErrors.password"
      class="mb-1"
    />

    <v-text-field
      v-model="confirmPassword"
      type="password"
      :label="t('auth.register.confirmPassword')"
      autocomplete="new-password"
      variant="outlined"
      rounded="lg"
      density="comfortable"
      prepend-inner-icon="mdi-lock-check-outline"
      :rules="confirmRules"
      :error-messages="backendErrors.confirmPassword"
      class="mb-1"
    />

    <v-btn
      type="submit"
      color="primary"
      size="large"
      block
      rounded="lg"
      :loading="loading"
      elevation="0"
      class="submit-btn mt-2"
    >
      {{ t('auth.register.submit') }}
      <v-icon end size="18">mdi-arrow-right</v-icon>
    </v-btn>
  </v-form>
</template>

<style scoped>
.auth-form {
  display: flex;
  flex-direction: column;
}

.name-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
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

@media (max-width: 480px) {
  .name-row {
    grid-template-columns: 1fr;
    gap: 0;
  }
}

@media (max-width: 900px) {
  .auth-form {
    flex: 1 1 auto;
  }
}
</style>
