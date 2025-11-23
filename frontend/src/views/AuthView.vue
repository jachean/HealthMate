<script setup>
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import AuthLayout from '@/components/auth/AuthLayout.vue'
import LoginForm from '@/components/auth/LoginForm.vue'
import RegisterForm from '@/components/auth/RegisterForm.vue'
import HealthTip from '@/components/ui/HealthTip.vue'
import useHealthTips from '@/composables/useHealthTips'

const route = useRoute()
const router = useRouter()

const { currentTip, currentTipIndex } = useHealthTips()

const isSignUp = ref(route.name === 'register')
const errorMessage = ref('')

watch(
  () => route.name,
  (name) => (isSignUp.value = name === 'register')
)

const toggleMode = () => {
  errorMessage.value = ''
  router.push({ name: isSignUp.value ? 'login' : 'register' })
}

const handleLoginSuccess = () => {
  const redirect = route.query.redirect || '/me'
  router.push(redirect)
}

const handleRegisterSuccess = () => {
  router.push({ name: 'login' })
}
const handleError = (msg) => (errorMessage.value = msg)
</script>

<template>
  <AuthLayout :errorMessage="errorMessage">
    <div class="auth-inner" :class="{ 'is-signup': isSignUp }">
      <section class="view login-view">
        <div class="side form-side">
          <h2 class="auth-title">Log in to Your Account</h2>
          <p class="auth-subtitle">Continue your onboarding experience.</p>

          <LoginForm
            @success="handleLoginSuccess"
            :current-tip="currentTip"
            :current-tip-index="currentTipIndex"
          />
        </div>

        <div class="side blue-side">
          <div class="blue-content">
            <h2>Don't Have an Account?</h2>
            <p>Create your account to begin.</p>
            <button class="outline-btn" @click="toggleMode">
              SIGN UP
            </button>
          </div>
        </div>
      </section>

      <section class="view signup-view">
        <div class="side form-side">
          <h2 class="auth-title">Sign Up</h2>
          <p class="auth-subtitle">Create your account.</p>

          <RegisterForm
            @success="handleRegisterSuccess"
            @error="handleError"
          />
        </div>

        <div class="side blue-side">
          <div class="blue-content">
            <h2>Already have an account?</h2>
            <p>Log in to continue.</p>
            <button class="outline-btn" @click="toggleMode">
              LOG IN
            </button>
          </div>
        </div>
      </section>
    </div>

    <template #desktop-tip>
      <div class="desktop-only-tip">
        <HealthTip
          :tip="currentTip"
          :index="currentTipIndex"
          mode="desktop"
        />
      </div>
    </template>
  </AuthLayout>
</template>

<style src="@/styles/auth/auth-view.css"></style>
