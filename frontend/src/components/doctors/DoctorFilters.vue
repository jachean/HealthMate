<script setup>
import { computed, ref } from 'vue'
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
  if (props.modelValue.availability) count++
  return count
})

const availabilityMode = computed(() => {
  const v = props.modelValue.availability
  if (!v) return 'any'
  if (v === 'this_week') return 'this_week'
  return 'date'
})

const availabilityDate = computed(() => {
  const v = props.modelValue.availability
  if (!v || v === 'this_week') return ''
  return v
})

const DAYS_AHEAD = Number(import.meta.env.VITE_BOOKING_DAYS_AHEAD ?? 30)

const todayStr = new Date().toISOString().slice(0, 10)

const maxDate = (() => {
  const d = new Date()
  d.setDate(d.getDate() + DAYS_AHEAD)
  return d
})()

const dateMenuOpen = ref(false)

function displayDate(iso) {
  if (!iso) return ''
  const [y, m, d] = iso.split('-')
  return `${d}/${m}/${y}`
}

function toISO(date) {
  if (!date) return ''
  const d = date instanceof Date ? date : new Date(date)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

function onAvailabilityModeChange(mode) {
  if (mode === 'any') {
    update('availability', null)
  } else if (mode === 'this_week') {
    update('availability', 'this_week')
  } else {
    update('availability', todayStr)
  }
}
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

    <!-- Availability filter -->
    <div class="availability-section">
      <div class="d-flex align-center ga-2 mb-3">
        <v-icon size="16" color="primary">mdi-calendar-check-outline</v-icon>
        <span class="text-body-2 font-weight-medium">{{ t('filter.availabilityLabel') }}</span>
      </div>

      <v-btn-toggle
        :model-value="availabilityMode"
        mandatory
        color="primary"
        density="compact"
        rounded="lg"
        divided
        @update:model-value="onAvailabilityModeChange"
      >
        <v-btn value="any" size="small" class="availability-btn">
          {{ t('filter.availabilityAny') }}
        </v-btn>
        <v-btn value="this_week" size="small" class="availability-btn">
          <v-icon start size="14">mdi-calendar-week-outline</v-icon>
          {{ t('filter.availabilityThisWeek') }}
        </v-btn>
        <v-btn value="date" size="small" class="availability-btn">
          <v-icon start size="14">mdi-calendar-cursor</v-icon>
          {{ t('filter.availabilityDate') }}
        </v-btn>
      </v-btn-toggle>

      <v-menu
        v-if="availabilityMode === 'date'"
        v-model="dateMenuOpen"
        :close-on-content-click="false"
        class="mt-3"
      >
        <template #activator="{ props: menuProps }">
          <v-text-field
            v-bind="menuProps"
            :model-value="displayDate(availabilityDate)"
            :label="t('filter.availabilityDate')"
            prepend-inner-icon="mdi-calendar"
            readonly
            variant="outlined"
            density="compact"
            hide-details
            clearable
            class="mt-3 date-input"
            @click:clear="update('availability', todayStr)"
          />
        </template>
        <v-date-picker
          :model-value="availabilityDate ? new Date(availabilityDate + 'T00:00:00') : undefined"
          :min="new Date(todayStr + 'T00:00:00')"
          :max="maxDate"
          hide-header
          @update:model-value="(d) => { update('availability', toISO(d)); dateMenuOpen = false }"
        />
      </v-menu>
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

.availability-section :deep(.v-btn-toggle) {
  flex-wrap: wrap;
  height: auto;
}

.availability-btn {
  text-transform: none;
  letter-spacing: normal;
  font-size: 0.8125rem;
}

.date-input {
  max-width: 220px;
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

  .date-input {
    max-width: 100%;
    width: 100%;
  }
}
</style>
