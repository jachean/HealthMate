<script setup>
import { ref } from 'vue'
import BaseTextField from '@/components/ui/BaseTextField.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
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
      emit('error', 'Unable to create account. Please try again.')
    }
    return
  }

  emit('success')
}
</script>

<template>
  <v-form ref="formRef" @submit.prevent="submit" class="auth-form">
    <div class="form-row two-cols">
      <BaseTextField
        v-model="firstName"
        label="First Name"
        :rules="firstNameRules"
        :error-messages="backendErrors.firstName"
      />
      <BaseTextField
        v-model="lastName"
        label="Last Name"
        :rules="lastNameRules"
        :error-messages="backendErrors.lastName"
      />
    </div>

    <BaseTextField
      v-model="email"
      type="email"
      label="Email"
      autocomplete="email"
      :rules="emailRules"
      :error-messages="backendErrors.email"
    />

    <BaseTextField
      v-model="username"
      label="Username"
      :rules="usernameRules"
      :error-messages="backendErrors.username"
    />

    <BaseTextField
      v-model="password"
      type="password"
      label="Password"
      autocomplete="new-password"
      :rules="passwordRules"
      :error-messages="backendErrors.password"
    />

    <BaseTextField
      v-model="confirmPassword"
      type="password"
      label="Confirm password"
      autocomplete="new-password"
      :rules="confirmRules"
      :error-messages="backendErrors.confirmPassword"
    />

    <BaseButton type="submit" block :loading="loading">
      SIGN UP
    </BaseButton>
  </v-form>
</template>

<style scoped src="@/styles/auth/register-form.css"></style>
