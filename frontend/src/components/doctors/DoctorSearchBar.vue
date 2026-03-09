<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  filtersActive: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'filters'])
const { t } = useI18n()
</script>

<template>
  <v-card class="search-bar-card" variant="flat">
    <div class="d-flex align-center ga-3">
      <v-text-field
        :model-value="modelValue"
        @update:model-value="emit('update:modelValue', $event)"
        :placeholder="t('doctors.searchPlaceholder')"
        prepend-inner-icon="mdi-magnify"
        variant="solo-filled"
        flat
        clearable
        hide-details
        density="comfortable"
        class="search-input flex-grow-1"
      />

      <v-btn
        :variant="filtersActive ? 'flat' : 'tonal'"
        :color="filtersActive ? 'primary' : undefined"
        prepend-icon="mdi-tune-vertical"
        @click="emit('filters')"
        class="filter-btn"
        height="48"
      >
        {{ t('doctors.filters') }}
      </v-btn>
    </div>
  </v-card>
</template>

<style scoped>
.search-bar-card {
  padding: 16px;
  background: linear-gradient(135deg, rgb(var(--v-theme-surface)) 0%, rgba(var(--v-theme-primary), 0.03) 100%);
  border: 1px solid rgba(var(--v-theme-primary), 0.08);
  border-radius: 16px;
}

.search-input :deep(.v-field) {
  border-radius: 12px;
}

.filter-btn {
  border-radius: 12px;
  text-transform: none;
  font-weight: 500;
  letter-spacing: 0;
  padding: 0 20px;
}

@media (max-width: 599px) {
  .search-bar-card {
    padding: 12px;
  }

  .filter-btn {
    padding: 0 14px;
    min-width: auto;
  }

  .filter-btn :deep(.v-btn__prepend) {
    margin-inline-end: 0;
  }

  .filter-btn :deep(.v-btn__content) {
    font-size: 0;
  }

  .filter-btn :deep(.v-btn__prepend .v-icon) {
    font-size: 20px;
  }
}
</style>
