import { defineStore } from 'pinia'
import api from '@/lib/api'

// Default filter state - single source of truth
const createDefaultFilters = () => ({
  city: [],
  clinic: [],
  specialty: [],
  acceptsInsurance: false,
  search: '',
})

function normalizeDoctor(dto) {
  return {
    id: dto.id,
    fullName: `${dto.firstName} ${dto.lastName}`,
    acceptsInsurance: dto.acceptsInsurance,
    clinic: dto.clinic
      ? { id: dto.clinic.id, name: dto.clinic.name, city: dto.clinic.city, address: dto.clinic.address }
      : null,
    specialties: dto.specialties?.map(s => ({ name: s.name, slug: s.slug })) ?? [],
    specialtySlugs: dto.specialties?.map(s => s.slug) ?? [],
    startingPrice: dto.startingPrice ?? null,
    averageRating: dto.averageRating ?? null,
    reviewCount: dto.reviewCount ?? 0,
  }
}

// Extract unique values from doctors and format as select options
function extractOptions(doctors, getter, formatter = v => ({ label: v, value: v })) {
  const unique = new Map()
  for (const doctor of doctors) {
    const result = getter(doctor)
    if (result) {
      if (Array.isArray(result)) {
        result.forEach(item => unique.set(item.value, item))
      } else {
        unique.set(result.value, result)
      }
    }
  }
  return [...unique.values()]
}

// Humanize slug: "general-practice" → "General Practice"
const humanizeSlug = slug => slug.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase())

// Check if filter matches (empty filter = match all)
const matchesFilter = (value, filterValues) =>
  filterValues.length === 0 || filterValues.includes(value)

// Check if any item in array matches filter
const matchesAnyFilter = (values, filterValues) =>
  filterValues.length === 0 || filterValues.some(f => values.includes(f))

// Check if doctor matches search query (name, clinic, city, or specialty)
const matchesSearch = (doctor, query) => {
  if (!query) return true
  const q = query.toLowerCase()
  return (
    doctor.fullName.toLowerCase().includes(q) ||
    doctor.clinic?.name.toLowerCase().includes(q) ||
    doctor.clinic?.city?.toLowerCase().includes(q) ||
    doctor.specialties.some(s => s.name.toLowerCase().includes(q))
  )
}

export const useDoctorsStore = defineStore('doctors', {
  state: () => ({
    items: [],
    allItems: [],
    loading: false,
    hasLoaded: false,
    filters: createDefaultFilters(),
    page: 1,
    perPage: 10,
  }),

  getters: {
    doctors: state => {
      const start = (state.page - 1) * state.perPage
      return state.items.slice(start, start + state.perPage)
    },

    totalDoctors: state => state.items.length,

    totalPages: state => Math.ceil(state.items.length / state.perPage),

    cityOptions: state => extractOptions(
      state.allItems,
      d => d.clinic?.city && { label: d.clinic.city, value: d.clinic.city }
    ),

    clinicOptions: state => extractOptions(
      state.allItems,
      d => d.clinic && { label: d.clinic.name, value: d.clinic.id }
    ),

    specialtyOptions: state => extractOptions(
      state.allItems,
      d => d.specialtySlugs.map(s => ({ label: humanizeSlug(s), value: s }))
    ),
  },

  actions: {
    async fetchAllDoctorsOnce() {
      if (this.allItems.length) return
      const { data } = await api.get('/api/doctors')
      this.allItems = data.map(normalizeDoctor)
    },

    async fetchDoctors() {
      this.loading = true

      // Snapshot filters to prevent reactive timing issues
      const { city, clinic, specialty, acceptsInsurance, search } = {
        city: [...(this.filters.city || [])],
        clinic: [...(this.filters.clinic || [])],
        specialty: [...(this.filters.specialty || [])],
        acceptsInsurance: !!this.filters.acceptsInsurance,
        search: (this.filters.search || '').trim(),
      }

      try {
        // Build backend params (optimization for single-value filters)
        const params = {}
        if (city.length === 1) params.city = city[0]
        if (clinic.length === 1) params.clinic = clinic[0]
        if (specialty.length === 1) params.specialty = specialty[0]
        if (acceptsInsurance) params.acceptsInsurance = 1

        const { data } = await api.get('/api/doctors', { params })

        // Apply client-side filtering for multi-select and search support
        this.items = data.map(normalizeDoctor).filter(d =>
          matchesSearch(d, search) &&
          matchesFilter(d.clinic?.city, city) &&
          matchesFilter(d.clinic?.id, clinic) &&
          matchesAnyFilter(d.specialtySlugs, specialty) &&
          (!acceptsInsurance || d.acceptsInsurance)
        )
      } finally {
        this.loading = false
        this.hasLoaded = true
      }
    },

    setFiltersFromRoute(query) {
      this.filters = {
        city: query.city ? [query.city] : [],
        clinic: query.clinic ? [Number(query.clinic)] : [],
        specialty: query.specialty ? [query.specialty] : [],
        acceptsInsurance: query.acceptsInsurance === '1',
        search: query.search || '',
      }
    },

    updateFilters(filters) {
      this.filters = { ...this.filters, ...filters }
      this.page = 1
      return this.fetchDoctors()
    },

    setSearch(query) {
      this.filters.search = query
      this.page = 1
      return this.fetchDoctors()
    },

    setPage(page) {
      this.page = page
    },

    resetFilters() {
      this.filters = createDefaultFilters()
      this.page = 1
      return this.fetchDoctors()
    },
  },
})
