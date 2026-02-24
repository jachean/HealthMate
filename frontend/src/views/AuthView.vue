<script setup>
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

import AuthLayout from '@/components/auth/AuthLayout.vue'
import LoginForm from '@/components/auth/LoginForm.vue'
import RegisterForm from '@/components/auth/RegisterForm.vue'
import HealthTip from '@/components/ui/HealthTip.vue'
import useHealthTips from '@/composables/useHealthTips'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()

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
  const redirect = route.query.redirect || '/'
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
      <!-- ── Login view ──────────────────────────────────────────────────── -->
      <section class="view login-view">
        <div class="side form-side">
          <h2 class="auth-title">{{ t('auth.login.title') }}</h2>
          <p class="auth-subtitle">{{ t('auth.login.subtitle') }}</p>

          <LoginForm
            @success="handleLoginSuccess"
            :current-tip="currentTip"
            :current-tip-index="currentTipIndex"
          />
        </div>

        <div class="side blue-side">
          <span class="blue-deco blue-deco-1" />
          <span class="blue-deco blue-deco-2" />
          <span class="blue-deco blue-deco-3" />
          <div class="blue-content">
            <div class="blue-icon-wrap">
              <v-icon size="28" color="white">mdi-account-plus-outline</v-icon>
            </div>
            <h2>{{ t('auth.panel.newTitle') }}</h2>
            <p>{{ t('auth.panel.newText') }}</p>
            <button class="outline-btn" @click="toggleMode">{{ t('auth.panel.signUp') }}</button>
          </div>
        </div>
      </section>

      <!-- ── Sign-up view ────────────────────────────────────────────────── -->
      <section class="view signup-view">
        <div class="side form-side">
          <h2 class="auth-title">{{ t('auth.register.title') }}</h2>
          <p class="auth-subtitle">{{ t('auth.register.subtitle') }}</p>

          <RegisterForm
            @success="handleRegisterSuccess"
            @error="handleError"
          />
        </div>

        <div class="side blue-side">
          <span class="blue-deco blue-deco-1" />
          <span class="blue-deco blue-deco-2" />
          <span class="blue-deco blue-deco-3" />
          <div class="blue-content">
            <div class="blue-icon-wrap">
              <v-icon size="28" color="white">mdi-login-variant</v-icon>
            </div>
            <h2>{{ t('auth.panel.welcomeTitle') }}</h2>
            <p>{{ t('auth.panel.welcomeText') }}</p>
            <button class="outline-btn" @click="toggleMode">{{ t('auth.panel.signIn') }}</button>
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
