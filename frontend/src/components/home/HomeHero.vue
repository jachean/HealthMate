<script setup>
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const { t } = useI18n()

function goToDoctors() {
  router.push({ path: '/doctors' })
}

function goToRegister() {
  router.push({ name: 'register' })
}
</script>

<template>
  <v-row align="center">
    <v-col cols="12" md="7" lg="6">
      <v-chip
        color="secondary"
        variant="tonal"
        size="small"
        class="mb-4"
        prepend-icon="mdi-shield-check-outline"
      >
        {{ t('hero.badge') }}
      </v-chip>

      <h1 class="hero-title mb-4">
        {{ t('hero.title') }}<br>
        <span class="text-primary">{{ t('hero.titleHighlight') }}</span>
      </h1>

      <p class="hero-subtitle text-body-1 text-medium-emphasis mb-8">
        {{ t('hero.subtitle') }}
      </p>

      <div class="hero-actions d-flex ga-3 flex-wrap mb-6">
        <v-btn
          color="primary"
          size="large"
          class="hero-btn"
          @click="goToDoctors"
        >
          <v-icon start>mdi-magnify</v-icon>
          {{ t('hero.findDoctor') }}
        </v-btn>
        <v-btn
          variant="outlined"
          size="large"
          class="hero-btn"
          @click="goToRegister"
        >
          {{ t('hero.createAccount') }}
        </v-btn>
      </div>

      <div class="hero-trust d-flex ga-6 flex-wrap text-body-2 text-medium-emphasis">
        <span class="d-flex align-center ga-1">
          <v-icon size="18" color="success">mdi-check-circle</v-icon>
          {{ t('hero.noBrowseAccount') }}
        </span>
        <span class="d-flex align-center ga-1">
          <v-icon size="18" color="success">mdi-check-circle</v-icon>
          {{ t('hero.freeToUse') }}
        </span>
      </div>
    </v-col>

    <v-col cols="12" md="5" lg="6" class="d-none d-md-flex justify-center align-center">
      <div class="hero-illustration">
        <div class="hero-circle"></div>

        <v-card class="hero-card hero-card--doctor" elevation="0">
          <div class="d-flex align-center ga-3 pa-4">
            <v-avatar color="primary" size="48">
              <v-icon color="white">mdi-doctor</v-icon>
            </v-avatar>
            <div>
              <div class="text-body-2 font-weight-bold">{{ t('hero.cardDoctor') }}</div>
              <div class="text-caption text-medium-emphasis">{{ t('hero.cardSpecialty') }}</div>
            </div>
          </div>
        </v-card>

        <v-card class="hero-card hero-card--appointment" elevation="0">
          <div class="pa-4">
            <div class="d-flex align-center ga-2 mb-2">
              <v-icon size="18" color="secondary">mdi-calendar-check</v-icon>
              <span class="text-body-2 font-weight-bold">{{ t('hero.cardConfirmed') }}</span>
            </div>
            <div class="text-caption text-medium-emphasis">{{ t('hero.cardTime') }}</div>
          </div>
        </v-card>

        <v-card class="hero-card hero-card--rating" rounded="pill" elevation="0">
          <div class="d-flex align-center ga-1 px-4 py-2">
            <v-icon size="16" color="warning">mdi-star</v-icon>
            <span class="text-body-2 font-weight-bold">4.9</span>
          </div>
        </v-card>
      </div>
    </v-col>
  </v-row>
</template>

<style scoped>
.hero-title {
  font-size: 2.75rem;
  font-weight: 800;
  line-height: 1.15;
  letter-spacing: -0.5px;
  color: rgb(var(--v-theme-on-background));
}

.hero-subtitle {
  font-size: 1.125rem;
  line-height: 1.6;
  max-width: 480px;
}

.hero-illustration {
  position: relative;
  width: 100%;
  max-width: 400px;
  aspect-ratio: 1;
}

.hero-circle {
  position: absolute;
  inset: 10%;
  border-radius: 50%;
  background: linear-gradient(
    135deg,
    rgba(var(--v-theme-primary), 0.08),
    rgba(var(--v-theme-secondary), 0.08)
  );
}

.hero-card {
  position: absolute;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
  animation: hero-float 6s ease-in-out infinite;
}

.hero-card--doctor {
  top: 10%;
  left: 5%;
  animation-delay: 0s;
}

.hero-card--appointment {
  top: 50%;
  right: 0;
  animation-delay: -2s;
}

.hero-card--rating {
  bottom: 15%;
  left: 15%;
  animation-delay: -4s;
}

@keyframes hero-float {
 0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

@media (max-width: 959px) {
  .hero-title {
    font-size: 1.85rem;
    text-align: center;
  }

  .hero-subtitle {
    text-align: center;
    max-width: none;
  }

  .hero-actions {
    justify-content: center;
  }

  .hero-btn {
    flex: 1 1 auto;
    min-width: 0;
  }

  .hero-trust {
    justify-content: center;
    gap: 12px;
  }
}

@media (max-width: 599px) {
  .hero-title {
    font-size: 1.6rem;
  }

  .hero-btn {
    width: 100%;
    flex: unset;
  }

  .hero-actions {
    flex-direction: column;
  }

  .hero-trust {
    flex-direction: column;
    align-items: center;
    gap: 8px;
  }
}
</style>
