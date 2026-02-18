<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const steps = computed(() => [
  {
    n: 1,
    icon: 'mdi-text-box-search-outline',
    title: t('howItWorks.step1Title'),
    text: t('howItWorks.step1Text'),
  },
  {
    n: 2,
    icon: 'mdi-doctor',
    title: t('howItWorks.step2Title'),
    text: t('howItWorks.step2Text'),
  },
  {
    n: 3,
    icon: 'mdi-calendar-check-outline',
    title: t('howItWorks.step3Title'),
    text: t('howItWorks.step3Text'),
  },
])
</script>

<template>
  <div>
    <div class="text-center mb-8">
      <h2 class="text-h5 font-weight-bold">{{ t('howItWorks.title') }}</h2>
      <p class="text-body-2 text-medium-emphasis mt-1">
        {{ t('howItWorks.subtitle') }}
      </p>
    </div>

    <v-row class="steps-row">
      <v-col
        v-for="(step, index) in steps"
        :key="step.n"
        cols="12"
        md="4"
        class="step-col"
      >
        <div
          v-if="index < steps.length - 1"
          class="step-connector d-none d-md-block"
        ></div>

        <div
          v-if="index < steps.length - 1"
          class="step-connector-vertical d-md-none"
        ></div>

        <div class="step-item text-center">
          <div class="step-number-wrapper mb-4">
            <div class="step-number">
              <v-icon size="28" color="white">{{ step.icon }}</v-icon>
            </div>
            <span class="step-badge">{{ step.n }}</span>
          </div>

          <h3 class="text-subtitle-1 font-weight-bold mb-1">{{ step.title }}</h3>
          <p class="text-body-2 text-medium-emphasis step-text mb-0">
            {{ step.text }}
          </p>
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<style scoped>
.steps-row {
  position: relative;
}

.step-col {
  position: relative;
}

.step-connector {
  position: absolute;
  top: 36px;
  left: calc(50% + 40px);
  width: calc(100% - 80px);
  height: 2px;
  border-top: 2px dashed rgba(var(--v-theme-primary), 0.25);
  z-index: 0;
}

.step-number-wrapper {
  position: relative;
  display: inline-block;
  z-index: 1;
}

.step-number {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)), rgb(var(--v-theme-primary-darken-1)));
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  box-shadow: 0 4px 16px rgba(var(--v-theme-primary), 0.25);
}

.step-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: rgb(var(--v-theme-secondary));
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid rgb(var(--v-theme-surface-variant));
}

.step-text {
  max-width: 260px;
  margin: 0 auto;
}

.step-connector-vertical {
  position: absolute;
  left: 50%;
  bottom: 0;
  transform: translateX(-50%);
  width: 2px;
  height: 24px;
  border-left: 2px dashed rgba(var(--v-theme-primary), 0.25);
}

@media (max-width: 959px) {
  .step-col {
    padding-bottom: 8px;
  }

  .step-number {
    width: 60px;
    height: 60px;
  }

  .step-badge {
    width: 22px;
    height: 22px;
    font-size: 0.7rem;
  }

  .step-text {
    max-width: 320px;
  }
}
</style>
