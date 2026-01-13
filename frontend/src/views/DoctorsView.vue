<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useDoctorsStore } from '@/stores/doctors'

import DoctorCard from '@/components/doctors/DoctorCard.vue'
import DoctorBookingDialog from '@/components/doctors/DoctorBookingDialog.vue'
import DoctorSearchBar from '@/components/doctors/DoctorSearchBar.vue'
import DoctorFilters from '@/components/doctors/DoctorFilters.vue'

const doctorsStore = useDoctorsStore()
const route = useRoute()

const showFilters = ref(false)
const selectedDoctor = ref(null)
const showDialog = ref(false)

function openDoctor(doctor) {
  selectedDoctor.value = doctor
  showDialog.value = true
}

async function init() {
  await doctorsStore.fetchAllDoctorsOnce()
  doctorsStore.setFiltersFromRoute(route.query)
  doctorsStore.fetchDoctors()
}

onMounted(init)

watch(
  () => route.query,
  () => {
    doctorsStore.setFiltersFromRoute(route.query)
    doctorsStore.fetchDoctors()
  }
)
</script>

<template>
  <v-container>
    <!-- 🔍 SEARCH BAR -->
    <DoctorSearchBar @filters="showFilters = !showFilters" />

    <!-- 🎛 FILTERS -->
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

    <!-- ⏳ LOADING BAR -->
    <v-progress-linear
      v-if="doctorsStore.loading"
      indeterminate
      color="primary"
      class="mb-4"
    />

    <!-- ℹ️ EMPTY STATE -->
    <v-alert
      v-else-if="
        doctorsStore.hasLoaded &&
        doctorsStore.doctors.length === 0
      "
      type="info"
      variant="tonal"
      class="mb-6"
    >
      No doctors match the selected filters.
    </v-alert>

    <!-- 👨‍⚕️ DOCTORS LIST (SAFE SPACING) -->
    <v-row
      v-else
      dense
      class="flex-column"
    >
      <v-col
        v-for="doctor in doctorsStore.doctors"
        :key="doctor.id"
        cols="12"
        class="pb-4"
      >
        <DoctorCard
          :doctor="doctor"
          @select="openDoctor"
        />
      </v-col>
    </v-row>

    <!-- 📅 BOOKING DIALOG -->
    <DoctorBookingDialog
      v-if="selectedDoctor"
      v-model="showDialog"
      :doctor="selectedDoctor"
    />
  </v-container>
</template>
