<script setup>
import { ref } from 'vue'
import BaseTextField from '@/components/ui/BaseTextField.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useAuthValidation } from '@/composables/useAuthValidation'

const emit = defineEmits(['success', 'error'])

const formRef = ref(null)

const firstName = ref('')
const lastName = ref('')
const email = ref('')
const username = ref('')
const password = ref('')
const confirmPassword = ref('')
const loading = ref(false)

const { registerRules } = useAuthValidation(password)

const firstNameRules = registerRules.firstName
const lastNameRules = registerRules.lastName
const emailRules = registerRules.email
const usernameRules = registerRules.username
const passwordRules = registerRules.password
const confirmRules = registerRules.confirmPassword

const submit = async () => {
  loading.value = true
  emit('error', '')

  const { valid } = await formRef.value.validate()
  if (!valid) {
    loading.value = false
    return
  }

  // TODO: Replace with real signup API
  await new Promise((r) => setTimeout(r, 500))

  emit('success')
  loading.value = false
}
</script>

<template>
  <v-form ref="formRef" @submit.prevent="submit" class="auth-form">
    <div class="form-row two-cols">
      <BaseTextField
        v-model="firstName"
        label="First Name"
        :rules="firstNameRules"
      />
      <BaseTextField
        v-model="lastName"
        label="Last Name"
        :rules="lastNameRules"
      />
    </div>

    <BaseTextField
      v-model="email"
      type="email"
      label="Email"
      autocomplete="email"
      :rules="emailRules"
    />

    <BaseTextField
      v-model="username"
      label="Username"
      :rules="usernameRules"
    />

    <BaseTextField
      v-model="password"
      type="password"
      label="Password"
      autocomplete="new-password"
      :rules="passwordRules"
    />

    <BaseTextField
      v-model="confirmPassword"
      type="password"
      label="Confirm password"
      autocomplete="new-password"
      :rules="confirmRules"
    />

    <BaseButton type="submit" block :loading="loading">
      SIGN UP
    </BaseButton>
  </v-form>
</template>

<style scoped src="@/styles/auth/register-form.css"></style>
