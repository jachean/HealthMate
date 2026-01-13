<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import TimeSlotButton from '@/components/doctors/TimeSlotButton.vue'
import { isoToDateKey, dateKeyToLabel } from '@/utils/date'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  doctor: { type: Object, required: true },
})

const emit = defineEmits(['update:modelValue', 'closed'])

const router = useRouter()
const auth = useAuthStore()

const loading = ref(false)
const slots = ref([])
const selectedDay = ref(null)
const selectedSlot = ref(null)

const booking = ref(false)
const bookingErrorCode = ref(null)

const showSuccessToast = ref(false)

onMounted(() => {
  loadAvailability()
})

async function loadAvailability() {
  loading.value = true
  slots.value = []
  selectedDay.value = null
  selectedSlot.value = null
  bookingErrorCode.value = null

  try {
    const { data } = await api.get(`/api/doctors/${props.doctor.id}/availability`)
    slots.value = Array.isArray(data) ? data : []
    selectedDay.value = dayKeys.value[0] ?? null
  } finally {
    loading.value = false
  }
}

const dayKeys = computed(() => {
  const keys = [...new Set(slots.value.map(s => isoToDateKey(s.startAt)))]
  keys.sort()
  return keys
})

const slotsForDay = computed(() => {
  if (!selectedDay.value) return []
  return slots.value.filter(s => isoToDateKey(s.startAt) === selectedDay.value)
})

function close() {
  emit('update:modelValue', false)
  emit('closed')
}

function goToLogin() {
  close()
  router.push({ name: 'login', query: { redirect: '/doctors' } })
}

async function confirmBooking() {
  if (!selectedSlot.value || booking.value) return

  if (!auth.token) {
    bookingErrorCode.value = 'AUTH_REQUIRED'
    return
  }

  booking.value = true
  bookingErrorCode.value = null

  try {
    await api.post('/api/appointments', {
      timeSlotId: selectedSlot.value.id,
    })

    showSuccessToast.value = true


    await loadAvailability()
     setTimeout(() => close(), 100)

  } catch (e) {
    bookingErrorCode.value = e?.response?.data?.error?.code || 'UNKNOWN'
  } finally {
    booking.value = false
  }
}
</script>

<template>
  <v-dialog
    :model-value="modelValue"
    max-width="1100"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="text-h6">Book appointment</v-card-title>

      <v-card-text>
        <v-row>
          <!-- LEFT -->
          <v-col cols="12" md="6">
            <div class="d-flex justify-center mb-4">
              <v-avatar color="primary" size="72">
                {{ (doctor.fullName || '').charAt(0) }}
              </v-avatar>
            </div>

            <div class="text-center mb-4">
              <div class="text-h6 font-weight-bold">{{ doctor.fullName }}</div>
              <div class="text-body-2 text-medium-emphasis">{{ doctor.clinic?.name }}</div>

              <div
                v-if="doctor.clinic?.city"
                class="text-body-2 text-medium-emphasis d-flex justify-center align-center"
              >
                <v-icon size="16" class="mr-1">mdi-map-marker-outline</v-icon>
                {{ doctor.clinic.city }}
              </div>
            </div>

            <v-divider class="my-4" />

            <v-progress-linear
              v-if="loading"
              indeterminate
              color="primary"
              class="mb-4"
            />

            <template v-else>
              <v-alert
                v-if="dayKeys.length === 0"
                type="info"
                variant="tonal"
                class="mb-4"
              >
                No available time slots.
              </v-alert>

              <template v-else>
                <div class="text-subtitle-2 font-weight-bold mb-2">Choose a day</div>

                <v-slide-group v-model="selectedDay" show-arrows class="mb-4">
                  <v-slide-group-item
                    v-for="day in dayKeys"
                    :key="day"
                    :value="day"
                    v-slot="{ isSelected, toggle }"
                  >
                    <v-chip
                      class="mr-2"
                      :color="isSelected ? 'primary' : undefined"
                      :variant="isSelected ? 'flat' : 'outlined'"
                      @click="toggle"
                    >
                      {{ dateKeyToLabel(day) }}
                    </v-chip>
                  </v-slide-group-item>
                </v-slide-group>

                <div class="text-subtitle-2 font-weight-bold mb-2">Available time slots</div>

                <v-row dense>
                  <v-col
                    v-for="slot in slotsForDay"
                    :key="slot.id"
                    cols="12"
                    sm="6"
                  >
                    <TimeSlotButton
                      :slot="slot"
                      :selected="selectedSlot?.id === slot.id"
                      @click="selectedSlot = slot"
                    />
                  </v-col>
                </v-row>
              </template>

              <v-alert
                v-if="bookingErrorCode === 'AUTH_REQUIRED'"
                type="warning"
                variant="tonal"
                class="mt-4"
              >
                You must be logged in to book an appointment.
                <div class="mt-2">
                  <v-btn color="primary" variant="flat" @click="goToLogin">
                    Go to login
                  </v-btn>
                </div>
              </v-alert>

              <v-alert
                v-else-if="bookingErrorCode"
                type="error"
                variant="tonal"
                class="mt-4"
              >
                Booking failed ({{ bookingErrorCode }}).
              </v-alert>

              <v-btn
                class="mt-4"
                color="primary"
                block
                :disabled="!selectedSlot"
                :loading="booking"
                @click="confirmBooking"
              >
                Confirm appointment
              </v-btn>
            </template>
          </v-col>

          <!-- RIGHT -->
          <v-col cols="12" md="6">
            <v-skeleton-loader type="image, paragraph, paragraph" />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </v-dialog>

  <v-snackbar
    v-model="showSuccessToast"
    color="success"
    timeout="3000"
    location="top right"
  >
    Appointment booked successfully
  </v-snackbar>
</template>
