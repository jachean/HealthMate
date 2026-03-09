<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { uploadUrl } from '@/utils/url'

const props = defineProps({
  doctor: { type: Object, required: true },
})

const emit = defineEmits(['select'])
const { t } = useI18n()

const doctorName = computed(() =>
  props.doctor.fullName || `${props.doctor.firstName || ''} ${props.doctor.lastName || ''}`.trim()
)

const initials = computed(() => {
  const name = doctorName.value
  const parts = name.split(' ').filter(Boolean)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }
  return name.slice(0, 2).toUpperCase()
})

const specialtyChips = computed(() => {
  const arr = props.doctor?.specialties || []
  return arr.map((s) => {
    if (typeof s === 'string') {
      return { key: s, label: s }
    }
    const slug = s.slug || ''
    const key = `specialty.${slug}`
    const translated = t(key)
    const label = translated !== key ? translated : (s.name || slug)
    return { key: slug || s.id || s.name, label }
  })
})

function formatPrice(price) {
  const num = parseFloat(price)
  return num % 1 === 0 ? num.toFixed(0) : num.toFixed(2)
}

function onSelect() {
  emit('select', props.doctor)
}
</script>

<template>
  <v-card
    class="doctor-card"
    variant="flat"
    @click="onSelect"
  >
    <div class="card-content">
      <v-avatar
        :color="doctor.avatarPath ? undefined : 'primary'"
        size="56"
        class="doctor-avatar"
      >
        <v-img v-if="doctor.avatarPath" :src="uploadUrl(doctor.avatarPath)" cover />
        <span v-else class="text-h6 font-weight-medium">{{ initials }}</span>
      </v-avatar>

      <div class="doctor-info">
        <h3 class="text-subtitle-1 font-weight-bold mb-1">
          Dr. {{ doctorName }}
        </h3>

        <div class="meta-row">
          <v-icon size="16">mdi-hospital-building</v-icon>
          <span>{{ doctor.clinic?.name || doctor.clinicName || 'Independent Practice' }}</span>
        </div>

        <div v-if="doctor.clinic?.city || doctor.city" class="meta-row">
          <v-icon size="16">mdi-map-marker-outline</v-icon>
          <span>{{ doctor.clinic?.city || doctor.city }}</span>
        </div>
      </div>

      <v-icon class="chevron-icon" color="grey-lighten-1">mdi-chevron-right</v-icon>
    </div>

    <v-divider class="my-3" />

    <div class="card-footer">
      <div class="specialties">
        <v-chip
          v-for="spec in specialtyChips"
          :key="spec.key"
          size="small"
          color="primary"
          variant="tonal"
          class="specialty-chip"
        >
          {{ spec.label }}
        </v-chip>
      </div>

      <div class="card-footer-right">
        <div v-if="doctor.averageRating !== null && doctor.averageRating !== undefined" class="rating-row">
          <v-rating
            :model-value="doctor.averageRating"
            readonly
            half-increments
            density="compact"
            size="small"
            color="amber"
            active-color="amber"
          />
          <span class="rating-count">{{ doctor.averageRating.toFixed(1) }} ({{ doctor.reviewCount }} {{ t('review.reviews') }})</span>
        </div>

        <v-chip
          v-if="doctor.startingPrice"
          size="small"
          color="primary"
          variant="tonal"
          class="price-chip"
        >
          {{ t('doctors.from') }} {{ formatPrice(doctor.startingPrice) }} RON
        </v-chip>

        <v-chip
          size="small"
          :color="doctor.acceptsInsurance ? 'success' : 'grey'"
          :variant="doctor.acceptsInsurance ? 'tonal' : 'outlined'"
          class="insurance-chip"
        >
          <v-icon start size="14">
            {{ doctor.acceptsInsurance ? 'mdi-shield-check' : 'mdi-shield-off-outline' }}
          </v-icon>
          {{ doctor.acceptsInsurance ? t('doctors.insurance') : t('doctors.noInsurance') }}
        </v-chip>
      </div>
    </div>
  </v-card>
</template>

<style scoped>
.doctor-card {
  padding: 20px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.doctor-card:hover {
  border-color: rgba(var(--v-theme-primary), 0.3);
  box-shadow: 0 4px 20px rgba(var(--v-theme-primary), 0.1);
  transform: translateY(-2px);
}

.card-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.doctor-avatar {
  flex-shrink: 0;
}

.doctor-info {
  flex-grow: 1;
  min-width: 0;
}

.meta-row {
  display: flex;
  align-items: center;
  gap: 6px;
  color: rgba(var(--v-theme-on-surface), 0.6);
  font-size: 0.875rem;
  margin-top: 2px;
}

.chevron-icon {
  flex-shrink: 0;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.doctor-card:hover .chevron-icon {
  opacity: 1;
}

.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px;
}

.specialties {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.specialty-chip {
  border-radius: 8px;
  font-weight: 500;
}

.card-footer-right {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
  flex-shrink: 0;
}

.price-chip {
  border-radius: 8px;
  font-weight: 600;
}

.insurance-chip {
  border-radius: 8px;
  font-weight: 500;
}

.rating-row {
  display: flex;
  align-items: center;
  gap: 4px;
}

.rating-count {
  font-size: 0.75rem;
  color: rgba(var(--v-theme-on-surface), 0.6);
  white-space: nowrap;
}

@media (max-width: 599px) {
  .doctor-card {
    padding: 16px;
  }

  .card-content {
    gap: 12px;
  }

  .doctor-avatar {
    width: 44px !important;
    height: 44px !important;
  }

  .doctor-avatar .text-h6 {
    font-size: 0.9rem !important;
  }

  .chevron-icon {
    display: none;
  }

  .card-footer {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
