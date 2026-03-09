<script setup>
import { computed } from 'vue'
import { formatSlotRange } from '@/utils/date'

const props = defineProps({
  slot: { type: Object, required: true },
  selected: { type: Boolean, default: false },
})

const timeLabel = computed(() => formatSlotRange(props.slot.startAt, props.slot.endAt))
</script>

<template>
  <button
    type="button"
    class="time-slot-btn"
    :class="{ 'time-slot-btn--selected': selected }"
  >
    <v-icon size="16" class="slot-icon">mdi-clock-outline</v-icon>
    <span class="slot-time">{{ timeLabel }}</span>
    <v-icon v-if="selected" size="16" class="check-icon">mdi-check</v-icon>
  </button>
</template>

<style scoped>
.time-slot-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  padding: 12px 16px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.15);
  border-radius: 10px;
  background: transparent;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.875rem;
  font-weight: 500;
}

.time-slot-btn:hover {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.04);
}

.time-slot-btn--selected {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.1);
  color: rgb(var(--v-theme-primary));
}

.slot-time {
  white-space: nowrap;
}

.slot-icon {
  opacity: 0.5;
}

.time-slot-btn--selected .slot-icon {
  opacity: 1;
  color: rgb(var(--v-theme-primary));
}

.check-icon {
  color: rgb(var(--v-theme-primary));
}

@media (max-width: 599px) {
  .time-slot-btn {
    padding: 10px 12px;
    font-size: 0.8125rem;
    gap: 6px;
  }
}
</style>
