<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const email = ref('doctor@example.com')
const password = ref('Doctor123!')

const submit = async () => {
  const ok = await auth.login(email.value, password.value)
  if (ok) {
    router.push(route.query.redirect || '/me')
  }
}
</script>

<template>
  <div class="login">
    <h1>HealthMate – Login</h1>

    <form @submit.prevent="submit">
      <div>
        <label>Email</label>
        <input v-model="email" type="email" required />
      </div>

      <div>
        <label>Password</label>
        <input v-model="password" type="password" required />
      </div>

      <button :disabled="auth.loading">
        {{ auth.loading ? 'Logging in...' : 'Login' }}
      </button>

      <p v-if="auth.error" class="error">{{ auth.error }}</p>
    </form>
  </div>
</template>

<style scoped>
.login {
  max-width: 400px;
  margin: 3rem auto;
  display: grid;
  gap: 0.75rem;
}
label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.25rem;
}
input {
  width: 100%;
  padding: 0.5rem;
  border-radius: 8px;
  border: 1px solid #ddd;
}
button {
  padding: 0.6rem 1rem;
  border-radius: 8px;
  border: none;
  cursor: pointer;
}
.error {
  color: red;
}
</style>
