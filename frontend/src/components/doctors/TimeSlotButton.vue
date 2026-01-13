<script setup>
import { computed } from 'vue'
import { formatSlotRange } from '@/utils/date'

const props = defineProps({
  slot: { type: Object, required: true },
  selected: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})

const label = computed(() => formatSlotRange(props.slot.startAt, props.slot.endAt))
</script>

<template>
  <v-btn
    :variant="selected ? 'flat' : 'outlined'"
    :color="selected ? 'primary' : undefined"
    class="w-100"
    size="small"
    :disabled="disabled"
  >
    {{ label }}
  </v-btn>
</template>
