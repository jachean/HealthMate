import { defineStore } from 'pinia'
import api from '@/lib/api'

function normalizeDoctor(dto) {
  return {
    id: dto.id,
    fullName: `${dto.firstName} ${dto.lastName}`,
    acceptsInsurance: dto.acceptsInsurance,

    clinic: dto.clinic
      ? {
        id: dto.clinic.id,
        name: dto.clinic.name,
        city: dto.clinic.city,
      }
      : null,

    specialties: Array.isArray(dto.specialties) ? dto.specialties.map(s => s.name) : [],
    specialtySlugs: Array.isArray(dto.specialties) ? dto.specialties.map(s => s.slug) : [],
  }
}

function includesAny(haystack, needles) {
  if (!Array.isArray(needles) || needles.length === 0) return true
  if (!Array.isArray(haystack) || haystack.length === 0) return false
  return needles.some(n => haystack.includes(n))
}

export const useDoctorsStore = defineStore('doctors', {
  state: () => ({
    items: [],
    allItems: [],
    loading: false,
    hasLoaded: false,

    filters: {
      city: [],
      clinic: [],
      specialty: [],
      acceptsInsurance: false,
    },
  }),

  getters: {
    doctors: state => state.items,

    cityOptions(state) {
      const set = new Set()
      state.allItems.forEach(d => {
        if (d.clinic?.city) set.add(d.clinic.city)
      })
      return [...set].map(c => ({ label: c, value: c }))
    },

    clinicOptions(state) {
      const map = new Map()
      state.allItems.forEach(d => {
        if (d.clinic) map.set(d.clinic.id, d.clinic.name)
      })
      return [...map.entries()].map(([id, name]) => ({
        label: name,
        value: id,
      }))
    },

    specialtyOptions(state) {
      const set = new Set()
      state.allItems.forEach(d => {
        d.specialtySlugs.forEach(s => set.add(s))
      })
      return [...set].map(s => ({
        label: s.replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase()),
        value: s,
      }))
    },
  },

  actions: {
    async fetchAllDoctorsOnce() {
      if (this.allItems.length) return
      const { data } = await api.get('/api/doctors')
      this.allItems = data.map(normalizeDoctor)
    },

    async fetchDoctors() {
      this.loading = true

      // snapshot filters once (prevents reactive timing weirdness)
      const city = Array.isArray(this.filters.city) ? [...this.filters.city] : []
      const clinic = Array.isArray(this.filters.clinic) ? [...this.filters.clinic] : []
      const specialty = Array.isArray(this.filters.specialty) ? [...this.filters.specialty] : []
      const acceptsInsurance = !!this.filters.acceptsInsurance

      try {
        const params = {}

        // backend supports single values → use as optimization only
        if (city.length === 1) params.city = city[0]
        if (clinic.length === 1) params.clinic = clinic[0]
        if (specialty.length === 1) params.specialty = specialty[0]
        if (acceptsInsurance) params.acceptsInsurance = 1

        const { data } = await api.get('/api/doctors', { params })
        const normalized = data.map(normalizeDoctor)

        // ✅ ALWAYS apply local filtering (supports multi-select reliably)
        const filtered = normalized.filter(d => {
          const matchesCity =
            city.length === 0 ? true : city.includes(d.clinic?.city)

          const matchesClinic =
            clinic.length === 0 ? true : clinic.includes(d.clinic?.id)

          const matchesSpecialty =
            specialty.length === 0 ? true : includesAny(d.specialtySlugs, specialty)

          const matchesInsurance =
            acceptsInsurance ? d.acceptsInsurance === true : true

          return matchesCity && matchesClinic && matchesSpecialty && matchesInsurance
        })

        this.items = filtered
      } finally {
        this.loading = false
        this.hasLoaded = true
      }
    },

    setFiltersFromRoute(query) {
      this.filters = {
        city: typeof query.city === 'string' ? [query.city] : [],
        clinic: typeof query.clinic === 'string' ? [Number(query.clinic)] : [],
        specialty: typeof query.specialty === 'string' ? [query.specialty] : [],
        acceptsInsurance: query.acceptsInsurance === '1',
      }
    },

    updateFilters(filters) {
      this.filters = filters
      return this.fetchDoctors()
    },

    resetFilters() {
      this.filters = {
        city: [],
        clinic: [],
        specialty: [],
        acceptsInsurance: false,
      }
      return this.fetchDoctors()
    },
  },
})
