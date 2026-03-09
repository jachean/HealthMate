<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import 'leaflet/dist/leaflet.css'
import L from 'leaflet'

const props = defineProps({
  address: { type: String, default: '' },
  city: { type: String, default: '' },
  clinicName: { type: String, default: '' },
  lat: { type: Number, default: null },
  lng: { type: Number, default: null },
})

const mapContainer = ref(null)
const loading = ref(true)
const error = ref(false)

let map = null
let marker = null
let resizeObserver = null

const fullAddress = () => {
  const parts = [props.address, props.city].filter(Boolean)
  return parts.join(', ')
}

async function geocodeAddress(address) {
  const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`
  const response = await fetch(url, {
    headers: { 'User-Agent': 'HealthMate/1.0' }
  })
  const data = await response.json()
  if (data.length > 0) {
    return { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) }
  }
  return null
}

async function initMap() {
  if (!mapContainer.value) return

  loading.value = true
  error.value = false

  try {
    let coords

    if (props.lat !== null && props.lng !== null) {
      coords = { lat: props.lat, lng: props.lng }
    } else {
      const address = fullAddress()
      if (!address) {
        error.value = true
        return
      }
      coords = await geocodeAddress(address)
      if (!coords) {
        error.value = true
        return
      }
    }

    if (map) {
      map.remove()
    }

    map = L.map(mapContainer.value).setView([coords.lat, coords.lng], 15)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map)

    // Custom marker icon
    const customIcon = L.divIcon({
      className: 'custom-marker',
      html: `<div class="marker-pin"></div>`,
      iconSize: [30, 40],
      iconAnchor: [15, 40],
      popupAnchor: [0, -40]
    })

    marker = L.marker([coords.lat, coords.lng], { icon: customIcon }).addTo(map)

    if (props.clinicName) {
      marker.bindPopup(`
        <strong>${props.clinicName}</strong><br>
        ${props.address || ''}<br>
        ${props.city || ''}
      `, { minWidth: 200 }).openPopup()
    }

    // Use ResizeObserver so Leaflet recalculates size once the container has real dimensions
    if (resizeObserver) resizeObserver.disconnect()
    resizeObserver = new ResizeObserver(() => {
      map && map.invalidateSize()
    })
    resizeObserver.observe(mapContainer.value)
  } catch (e) {
    console.error('Map error:', e)
    error.value = true
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  initMap()
})

onBeforeUnmount(() => {
  if (resizeObserver) resizeObserver.disconnect()
  if (map) map.remove()
})

watch(() => [props.address, props.city], () => {
  initMap()
})
</script>

<template>
  <div class="clinic-map">
    <!-- Loading State -->
    <div v-if="loading" class="map-loading">
      <v-progress-circular indeterminate color="primary" size="32" />
      <p class="text-body-2 text-medium-emphasis mt-2">Loading map...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="map-error">
      <v-icon size="48" color="grey-lighten-1">mdi-map-marker-off</v-icon>
      <p class="text-body-2 text-medium-emphasis mt-2">Unable to load map</p>
    </div>

    <!-- Map Container -->
    <div ref="mapContainer" class="map-container" :class="{ 'map-hidden': loading || error }" />

    <!-- Address Footer -->
    <div v-if="!loading && !error" class="map-footer">
      <v-icon size="16" class="mr-1">mdi-map-marker</v-icon>
      <span class="text-body-2">{{ fullAddress() }}</span>
    </div>
  </div>
</template>

<style scoped>
.clinic-map {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
  overflow: hidden;
  background: rgba(var(--v-theme-on-surface), 0.03);
}

.map-container {
  flex: 1;
  min-height: 0;
}

.map-hidden {
  display: none;
}

.map-loading,
.map-error {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.map-footer {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  background: rgba(var(--v-theme-surface), 0.95);
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

/* Custom marker styling */
:deep(.custom-marker) {
  background: transparent;
  border: none;
}

:deep(.marker-pin) {
  width: 30px;
  height: 40px;
  background: rgb(var(--v-theme-primary));
  border-radius: 50% 50% 50% 0;
  transform: rotate(-45deg);
  position: relative;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

:deep(.marker-pin::after) {
  content: '';
  width: 14px;
  height: 14px;
  background: white;
  border-radius: 50%;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

:deep(.leaflet-popup-content-wrapper) {
  border-radius: 10px;
}

:deep(.leaflet-popup-content) {
  margin: 12px 16px;
  line-height: 1.5;
}
</style>
