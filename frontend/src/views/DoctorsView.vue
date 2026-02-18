<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useDoctorsStore } from '@/stores/doctors'

import DoctorCard from '@/components/doctors/DoctorCard.vue'
import DoctorBookingDialog from '@/components/doctors/DoctorBookingDialog.vue'
import DoctorSearchBar from '@/components/doctors/DoctorSearchBar.vue'
import DoctorFilters from '@/components/doctors/DoctorFilters.vue'
import PaginationCarousel from '@/components/ui/PaginationCarousel.vue'

const doctorsStore = useDoctorsStore()
const route = useRoute()
const { t } = useI18n()

const showFilters = ref(false)
const selectedDoctor = ref(null)
const showDialog = ref(false)
const searchQuery = ref('')

let searchTimeout = null
function onSearchUpdate(value) {
  searchQuery.value = value
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    doctorsStore.setSearch(value)
  }, 300)
}

function openDoctor(doctor) {
  selectedDoctor.value = doctor
  showDialog.value = true
}

async function init() {
  await doctorsStore.fetchAllDoctorsOnce()
  doctorsStore.setFiltersFromRoute(route.query)
  searchQuery.value = doctorsStore.filters.search
  doctorsStore.fetchDoctors()
}

onMounted(init)

watch(
  () => route.query,
  () => {
    doctorsStore.setFiltersFromRoute(route.query)
    searchQuery.value = doctorsStore.filters.search
    doctorsStore.fetchDoctors()
  }
)
</script>

<template>
  <v-container class="doctors-page py-6">
    <header class="page-header mb-6">
      <h1 class="text-h4 font-weight-bold mb-1">{{ t('doctors.pageTitle') }}</h1>
      <p class="text-body-1 text-medium-emphasis">
        {{ t('doctors.pageSubtitle') }}
      </p>
    </header>

    <DoctorSearchBar
      :model-value="searchQuery"
      :filters-active="showFilters"
      @update:model-value="onSearchUpdate"
      @filters="showFilters = !showFilters"
      class="mb-4"
    />

    <v-expand-transition>
      <DoctorFilters
        v-if="showFilters"
        :model-value="doctorsStore.filters"
        :cities="doctorsStore.cityOptions"
        :clinics="doctorsStore.clinicOptions"
        :specialties="doctorsStore.specialtyOptions"
        @update:model-value="doctorsStore.updateFilters"
        @reset="doctorsStore.resetFilters"
        class="mb-6"
      />
    </v-expand-transition>

    <div
      v-if="doctorsStore.hasLoaded && !doctorsStore.loading"
      class="results-summary mb-4"
    >
      <v-icon size="18" class="mr-1">mdi-doctor</v-icon>
      <span class="text-body-2">
        <strong>{{ doctorsStore.totalDoctors }}</strong> {{ doctorsStore.totalDoctors === 1 ? t('doctors.doctorFound') : t('doctors.doctorsFound') }}
      </span>
    </div>

    <div v-if="doctorsStore.loading" class="loading-state">
      <v-progress-circular
        indeterminate
        color="primary"
        size="48"
      />
      <p class="text-body-2 text-medium-emphasis mt-3">{{ t('doctors.finding') }}</p>
    </div>

    <v-card
      v-else-if="doctorsStore.hasLoaded && doctorsStore.doctors.length === 0"
      class="empty-state text-center pa-8"
      variant="flat"
    >
      <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-account-search-outline</v-icon>
      <h3 class="text-h6 mb-2">{{ t('doctors.noResults') }}</h3>
      <p class="text-body-2 text-medium-emphasis mb-4">
        {{ t('doctors.noResultsHint') }}
      </p>
      <v-btn
        variant="tonal"
        color="primary"
        @click="doctorsStore.resetFilters(); searchQuery = ''"
      >
        {{ t('doctors.clearFilters') }}
      </v-btn>
    </v-card>

    <template v-else-if="doctorsStore.hasLoaded">
      <div class="doctors-grid">
        <DoctorCard
          v-for="doctor in doctorsStore.doctors"
          :key="doctor.id"
          :doctor="doctor"
          @select="openDoctor"
        />
      </div>

      <div v-if="doctorsStore.totalPages > 1" class="pagination-wrapper mt-6">
        <PaginationCarousel
          :model-value="doctorsStore.page"
          :length="doctorsStore.totalPages"
          :visible-count="5"
          @update:model-value="doctorsStore.setPage"
        />
        <p class="text-caption text-medium-emphasis text-center mt-2">
          Page {{ doctorsStore.page }} of {{ doctorsStore.totalPages }}
        </p>
      </div>
    </template>

    <DoctorBookingDialog
      v-if="selectedDoctor"
      v-model="showDialog"
      :doctor="selectedDoctor"
    />
  </v-container>
</template>

<style scoped>
.doctors-page {
  max-width: 900px;
}

.page-header {
  text-align: center;
}

.results-summary {
  display: flex;
  align-items: center;
  color: rgba(var(--v-theme-on-surface), 0.7);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 64px 0;
}

.empty-state {
  background: rgba(var(--v-theme-primary), 0.02);
  border: 1px dashed rgba(var(--v-theme-primary), 0.2);
  border-radius: 16px;
}

.doctors-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.pagination-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
}

@media (max-width: 599px) {
  .doctors-page {
    padding-left: 12px !important;
    padding-right: 12px !important;
    padding-top: 16px !important;
  }

  .page-header h1 {
    font-size: 1.5rem !important;
  }

  .page-header p {
    font-size: 0.875rem;
  }

  .doctors-grid {
    gap: 12px;
  }

  .loading-state {
    padding: 40px 0;
  }

  .empty-state {
    padding: 32px 16px !important;
  }
}
</style>
