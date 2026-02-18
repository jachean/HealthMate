<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  cities: Array,
  clinics: Array,
  specialties: Array,
})

const emit = defineEmits(['update:modelValue', 'reset'])
const { t } = useI18n()

function update(key, value) {
  emit('update:modelValue', {
    ...props.modelValue,
    [key]: value,
  })
}

const activeFilterCount = computed(() => {
  let count = 0
  if (props.modelValue.city?.length) count += props.modelValue.city.length
  if (props.modelValue.clinic?.length) count += props.modelValue.clinic.length
  if (props.modelValue.specialty?.length) count += props.modelValue.specialty.length
  if (props.modelValue.acceptsInsurance) count++
  return count
})
</script>

<template>
  <v-card class="filters-card" variant="flat">
    <div class="filters-header">
      <div class="d-flex align-center ga-2">
        <v-icon color="primary" size="20">mdi-filter-variant</v-icon>
        <span class="text-subtitle-1 font-weight-medium">{{ t('filter.refine') }}</span>
        <v-chip
          v-if="activeFilterCount > 0"
          size="x-small"
          color="primary"
          class="ml-1"
        >
          {{ activeFilterCount }}
        </v-chip>
      </div>
      <v-btn
        variant="text"
        color="primary"
        size="small"
        @click="$emit('reset')"
        :disabled="activeFilterCount === 0"
      >
        {{ t('filter.clearAll') }}
      </v-btn>
    </div>

    <v-divider class="my-3" />

    <div class="filters-grid">
      <v-select
        :label="t('filter.city')"
        multiple
        chips
        closable-chips
        clearable
        :items="cities"
        item-title="label"
        item-value="value"
        :model-value="modelValue.city"
        @update:model-value="update('city', $event)"
        variant="outlined"
        density="comfortable"
        hide-details
        prepend-inner-icon="mdi-city-variant-outline"
        class="filter-select"
        :menu-props="{ closeOnContentClick: false, closeOnScroll: true }"
      />

      <v-select
        :label="t('filter.clinic')"
        multiple
        chips
        closable-chips
        clearable
        :items="clinics"
        item-title="label"
        item-value="value"
        :model-value="modelValue.clinic"
        @update:model-value="update('clinic', $event)"
        variant="outlined"
        density="comfortable"
        hide-details
        prepend-inner-icon="mdi-hospital-building"
        class="filter-select"
        :menu-props="{ closeOnContentClick: false, closeOnScroll: true }"
      />

      <v-select
        :label="t('filter.specialty')"
        multiple
        chips
        closable-chips
        clearable
        :items="specialties"
        item-title="label"
        item-value="value"
        :model-value="modelValue.specialty"
        @update:model-value="update('specialty', $event)"
        variant="outlined"
        density="comfortable"
        hide-details
        prepend-inner-icon="mdi-stethoscope"
        class="filter-select"
        :menu-props="{ closeOnContentClick: false, closeOnScroll: true }"
      />
    </div>

    <v-divider class="my-3" />

    <v-checkbox
      :label="t('filter.insuranceOnly')"
      :model-value="modelValue.acceptsInsurance"
      @update:model-value="update('acceptsInsurance', $event)"
      color="primary"
      hide-details
      density="comfortable"
    >
      <template #label>
        <div class="d-flex align-center ga-2">
          <v-icon size="18" color="success">mdi-shield-check</v-icon>
          <span>{{ t('filter.insuranceOnly') }}</span>
        </div>
      </template>
    </v-checkbox>
  </v-card>
</template>

<style scoped>
.filters-card {
  padding: 20px;
  background: rgb(var(--v-theme-surface));
  border: 1px solid rgba(var(--v-theme-primary), 0.12);
  border-radius: 16px;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.filter-select :deep(.v-field) {
  border-radius: 10px;
}

.filter-select :deep(.v-chip) {
  border-radius: 8px;
}

@media (max-width: 599px) {
  .filters-card {
    padding: 16px;
  }

  .filters-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .filters-header {
    flex-wrap: wrap;
    gap: 8px;
  }
}
</style>
