<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const items = computed(() => [
  {
    icon: 'mdi-account-search-outline',
    title: t('trust.browseTitle'),
    text: t('trust.browseText'),
    color: 'primary',
  },
  {
    icon: 'mdi-hospital-building',
    title: t('trust.clinicsTitle'),
    text: t('trust.clinicsText'),
    color: 'secondary',
  },
  {
    icon: 'mdi-lightning-bolt-outline',
    title: t('trust.fastTitle'),
    text: t('trust.fastText'),
    color: 'accent',
  },
])
</script>

<template>
  <v-row>
    <v-col
      v-for="item in items"
      :key="item.title"
      cols="12"
      md="4"
    >
      <div class="trust-item text-center text-md-left">
        <div
          class="trust-icon-wrapper mb-4"
          :style="{ background: `rgba(var(--v-theme-${item.color}), 0.1)` }"
        >
          <v-icon :color="item.color" size="28">{{ item.icon }}</v-icon>
        </div>
        <h3 class="text-subtitle-1 font-weight-bold mb-1">{{ item.title }}</h3>
        <p class="text-body-2 text-medium-emphasis mb-0">{{ item.text }}</p>
      </div>
    </v-col>
  </v-row>
</template>

<style scoped>
.trust-icon-wrapper {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 16px;
}

@media (max-width: 959px) {
  .trust-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px 0;
  }

  .trust-icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 14px;
  }
}
</style>
