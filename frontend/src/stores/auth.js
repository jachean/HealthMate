import { defineStore } from 'pinia'
import api from '@/lib/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('hm_token') || null,
    user: null,
    loading: false,
    error: null,
    clinicAdminClinicId: null,
  }),

  getters: {
    isAdmin: (state) => state.user?.roles?.includes('ROLE_ADMIN') ?? false,
    isClinicAdmin: (state) =>
      (state.user?.roles?.includes('ROLE_CLINIC_ADMIN') ?? false) &&
      !(state.user?.roles?.includes('ROLE_ADMIN') ?? false),
    clinicAdminClinicId: (state) => state.clinicAdminClinicId,
  },

  actions: {
    async login(email, password) {
      this.loading = true
      this.error = null

      try {
        const { data } = await api.post('/api/login', {
          email,
          password,
        })

        this.token = data.token
        localStorage.setItem('hm_token', data.token)
        await this.fetchMe()
        return true
      } catch (e) {
        console.error('Login error:', e.response?.status, e.response?.data)
        this.error = e.response?.data?.message || 'Invalid credentials'
        this.token = null
        localStorage.removeItem('hm_token')
        return false
      } finally {
        this.loading = false
      }
    },

    async fetchMe() {
      if (!this.token) return
      try {
        const { data } = await api.get('/api/me')
        this.user = data
        this.clinicAdminClinicId = data.clinicAdminClinicId ?? null
      } catch {
        this.logout()
      }
    },

    logout() {
      this.token = null
      this.user = null
      this.clinicAdminClinicId = null
      localStorage.removeItem('hm_token')
    },
  },
})
