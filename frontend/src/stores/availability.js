import { defineStore } from 'pinia'
import api from '@/lib/api'

export const useAvailabilityStore = defineStore('availability', {
  state: () => ({
    slots: [],
    loading: false,
  }),

  getters: {
    slotsByDay(state) {
      return state.slots.reduce((acc, slot) => {
        const day = slot.startAt.slice(0, 10) // YYYY-MM-DD
        acc[day] = acc[day] || []
        acc[day].push(slot)
        return acc
      }, {})
    },

    days() {
      return Object.keys(this.slotsByDay)
    },
  },

  actions: {
    async fetchAvailability(doctorId) {
      this.loading = true
      try {
        const { data } = await api.get(
          `/api/doctors/${doctorId}/availability`
        )
        this.slots = data
      } finally {
        this.loading = false
      }
    },

    reset() {
      this.slots = []
    },
  },
})
