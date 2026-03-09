<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminGetAnalytics } from '@/services/adminService'

const { t } = useI18n()

const loading = ref(false)
const error = ref(false)
const period = ref('month')
const data = ref(null)

const periods = [
  { value: 'day',   label: () => t('admin.analytics.period.day') },
  { value: 'week',  label: () => t('admin.analytics.period.week') },
  { value: 'month', label: () => t('admin.analytics.period.month') },
]

async function load() {
  loading.value = true
  error.value = false
  try {
    data.value = await adminGetAnalytics(period.value)
  } catch {
    error.value = true
  } finally {
    loading.value = false
  }
}

async function setPeriod(p) {
  period.value = p
  await load()
}

onMounted(load)

// ── Chart helpers ─────────────────────────────────────────────────────────────
const chartMax = computed(() => {
  if (!data.value?.appointmentsByPeriod?.length) return 1
  return Math.max(...data.value.appointmentsByPeriod.map(d => d.booked + d.cancelled), 1)
})

function barPct(value) {
  return Math.round((value / chartMax.value) * 100)
}

function formatChartLabel(raw) {
  if (!raw) return ''
  // Month: "2026-03" → "Mar 26"
  if (/^\d{4}-\d{2}$/.test(raw)) {
    const [y, m] = raw.split('-')
    return new Date(+y, +m - 1).toLocaleDateString('en', { month: 'short', year: '2-digit' })
  }
  // Week: "2026-W10" → "W10"
  if (/W\d{2}$/.test(raw)) return raw.split('-')[1]
  // Day: "2026-03-05" → "Mar 5"
  if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
    return new Date(raw + 'T00:00:00').toLocaleDateString('en', { month: 'short', day: 'numeric' })
  }
  return raw
}

// ── Revenue chart helpers ──────────────────────────────────────────────────────
const revenueMax = computed(() => {
  if (!data.value?.revenue?.revenueByPeriod?.length) return 1
  return Math.max(...data.value.revenue.revenueByPeriod.map(d => d.revenue), 1)
})

function revBarPct(value) {
  return Math.round((value / revenueMax.value) * 100)
}

// ── Misc helpers ──────────────────────────────────────────────────────────────
function starLabel(rating) {
  return (+rating % 1 ? '½' : +rating) + ' ★'
}

const ratingKeys = ['5', '4', '3', '2', '1', '0.5']

function ratingTotal(dist) {
  return Object.values(dist).reduce((s, v) => s + v, 0) || 1
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center ga-3 mb-6">
      <v-avatar color="primary" variant="tonal" rounded="lg" size="44">
        <v-icon size="24">mdi-chart-bar</v-icon>
      </v-avatar>
      <div>
        <h1 class="text-h5 font-weight-bold">{{ t('admin.analytics.title') }}</h1>
        <p class="text-body-2 text-medium-emphasis mb-0">{{ t('admin.analytics.subtitle') }}</p>
      </div>
    </div>

    <!-- Error -->
    <v-alert v-if="error" type="error" density="compact" class="mb-4">
      {{ t('admin.errors.loadFailed') }}
    </v-alert>

    <!-- Loading skeleton -->
    <template v-if="loading && !data">
      <v-row dense class="mb-4">
        <v-col v-for="i in 4" :key="i" cols="12" sm="6" lg="3">
          <v-skeleton-loader type="card" />
        </v-col>
      </v-row>
      <v-skeleton-loader type="card" class="mb-4" />
      <v-row dense>
        <v-col cols="12" md="6"><v-skeleton-loader type="card" /></v-col>
        <v-col cols="12" md="6"><v-skeleton-loader type="card" /></v-col>
      </v-row>
    </template>

    <template v-else-if="data">
      <!-- ── KPI cards ─────────────────────────────────────────────────────── -->
      <v-row dense class="mb-2">
        <v-col cols="12" sm="6" lg="3">
          <v-card variant="tonal" color="primary" rounded="lg">
            <v-card-text class="pa-4">
              <div class="d-flex align-center justify-space-between mb-2">
                <span class="text-caption font-weight-medium text-uppercase">{{ t('admin.analytics.kpi.total') }}</span>
                <v-icon size="20" color="primary">mdi-calendar-check</v-icon>
              </div>
              <div class="text-h4 font-weight-bold">{{ data.summary.totalAppointments }}</div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" lg="3">
          <v-card variant="tonal" color="success" rounded="lg">
            <v-card-text class="pa-4">
              <div class="d-flex align-center justify-space-between mb-2">
                <span class="text-caption font-weight-medium text-uppercase">{{ t('admin.analytics.kpi.booked') }}</span>
                <v-icon size="20" color="success">mdi-check-circle</v-icon>
              </div>
              <div class="text-h4 font-weight-bold">{{ data.summary.bookedAppointments }}</div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" lg="3">
          <v-card variant="tonal" color="error" rounded="lg">
            <v-card-text class="pa-4">
              <div class="d-flex align-center justify-space-between mb-2">
                <span class="text-caption font-weight-medium text-uppercase">{{ t('admin.analytics.kpi.cancellationRate') }}</span>
                <v-icon size="20" color="error">mdi-cancel</v-icon>
              </div>
              <div class="text-h4 font-weight-bold">{{ data.summary.cancellationRate }}<span class="text-h6">%</span></div>
              <div class="text-caption text-medium-emphasis">{{ data.summary.cancelledAppointments }} {{ t('admin.analytics.kpi.cancelled') }}</div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" lg="3">
          <v-card variant="tonal" color="warning" rounded="lg">
            <v-card-text class="pa-4">
              <div class="d-flex align-center justify-space-between mb-2">
                <span class="text-caption font-weight-medium text-uppercase">{{ t('admin.analytics.kpi.avgRating') }}</span>
                <v-icon size="20" color="warning">mdi-star</v-icon>
              </div>
              <div class="text-h4 font-weight-bold">
                {{ data.summary.averageRating ?? '—' }}
                <span v-if="data.summary.averageRating" class="text-h6">/ 5</span>
              </div>
              <div class="text-caption text-medium-emphasis">{{ data.summary.totalReviews }} {{ t('admin.analytics.kpi.reviews') }}</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- ── Appointments chart ───────────────────────────────────────────── -->
      <v-card rounded="lg" class="mb-4">
        <v-card-text class="pa-4">
          <div class="d-flex align-center justify-space-between flex-wrap ga-2 mb-4">
            <div class="text-subtitle-1 font-weight-bold">{{ t('admin.analytics.chart.title') }}</div>
            <div class="d-flex ga-1">
              <v-btn
                v-for="p in periods"
                :key="p.value"
                size="small"
                :variant="period === p.value ? 'flat' : 'tonal'"
                :color="period === p.value ? 'primary' : undefined"
                :loading="loading && period === p.value"
                @click="setPeriod(p.value)"
              >
                {{ p.label() }}
              </v-btn>
            </div>
          </div>

          <!-- Legend -->
          <div class="d-flex ga-4 mb-3">
            <div class="d-flex align-center ga-1">
              <div class="legend-dot" style="background: rgb(var(--v-theme-primary))"></div>
              <span class="text-caption">{{ t('admin.analytics.chart.booked') }}</span>
            </div>
            <div class="d-flex align-center ga-1">
              <div class="legend-dot" style="background: rgb(var(--v-theme-error))"></div>
              <span class="text-caption">{{ t('admin.analytics.chart.cancelled') }}</span>
            </div>
          </div>

          <!-- Bar chart -->
          <div v-if="data.appointmentsByPeriod.length" class="bar-chart-wrap">
            <div class="bar-chart">
              <div
                v-for="item in data.appointmentsByPeriod"
                :key="item.label"
                class="bar-col"
              >
                <v-tooltip :text="`${t('admin.analytics.chart.booked')}: ${item.booked} · ${t('admin.analytics.chart.cancelled')}: ${item.cancelled}`" location="top">
                  <template #activator="{ props }">
                    <div v-bind="props" class="bar-stack">
                      <div
                        class="bar-segment bar-cancelled"
                        :style="{ height: barPct(item.cancelled) + '%' }"
                      ></div>
                      <div
                        class="bar-segment bar-booked"
                        :style="{ height: barPct(item.booked) + '%' }"
                      ></div>
                    </div>
                  </template>
                </v-tooltip>
                <div class="bar-label">{{ formatChartLabel(item.label) }}</div>
              </div>
            </div>
          </div>
          <div v-else class="text-center text-medium-emphasis py-8">
            {{ t('admin.analytics.chart.noData') }}
          </div>
        </v-card-text>
      </v-card>

      <!-- ── Top doctors & specialties ────────────────────────────────────── -->
      <v-row dense class="mb-4">
        <v-col cols="12" md="6">
          <v-card rounded="lg" height="100%">
            <v-card-text class="pa-4">
              <div class="text-subtitle-1 font-weight-bold mb-3">
                <v-icon size="18" class="mr-1">mdi-doctor</v-icon>
                {{ t('admin.analytics.topDoctors.title') }}
              </div>
              <div v-if="data.topDoctors.length" class="ranking-list">
                <div
                  v-for="(doc, i) in data.topDoctors"
                  :key="doc.id"
                  class="ranking-item"
                >
                  <span class="rank-num text-medium-emphasis">{{ i + 1 }}</span>
                  <div class="rank-info">
                    <div class="text-body-2 font-weight-medium">{{ doc.name }}</div>
                    <v-progress-linear
                      :model-value="barPct(doc.appointmentCount)"
                      color="primary"
                      bg-color="transparent"
                      rounded
                      height="4"
                      class="mt-1"
                    />
                  </div>
                  <div class="rank-meta">
                    <span class="text-body-2 font-weight-bold">{{ doc.appointmentCount }}</span>
                    <span v-if="doc.averageRating" class="text-caption text-medium-emphasis ml-1">
                      ★ {{ doc.averageRating }}
                    </span>
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-medium-emphasis py-6">{{ t('admin.analytics.noData') }}</div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card rounded="lg" height="100%">
            <v-card-text class="pa-4">
              <div class="text-subtitle-1 font-weight-bold mb-3">
                <v-icon size="18" class="mr-1">mdi-stethoscope</v-icon>
                {{ t('admin.analytics.topSpecialties.title') }}
              </div>
              <div v-if="data.topSpecialties.length" class="ranking-list">
                <div
                  v-for="(sp, i) in data.topSpecialties"
                  :key="sp.id"
                  class="ranking-item"
                >
                  <span class="rank-num text-medium-emphasis">{{ i + 1 }}</span>
                  <div class="rank-info">
                    <div class="text-body-2 font-weight-medium">{{ sp.name }}</div>
                    <v-progress-linear
                      :model-value="barPct(sp.appointmentCount)"
                      color="secondary"
                      bg-color="transparent"
                      rounded
                      height="4"
                      class="mt-1"
                    />
                  </div>
                  <span class="text-body-2 font-weight-bold">{{ sp.appointmentCount }}</span>
                </div>
              </div>
              <div v-else class="text-center text-medium-emphasis py-6">{{ t('admin.analytics.noData') }}</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- ── Clinic occupancy & Review scores ─────────────────────────────── -->
      <v-row dense>
        <v-col cols="12" md="6">
          <v-card rounded="lg" height="100%">
            <v-card-text class="pa-4">
              <div class="text-subtitle-1 font-weight-bold mb-1">
                <v-icon size="18" class="mr-1">mdi-hospital-building</v-icon>
                {{ t('admin.analytics.clinicOccupancy.title') }}
              </div>
              <p class="text-caption text-medium-emphasis mb-3">{{ t('admin.analytics.clinicOccupancy.hint') }}</p>
              <div v-if="data.clinicOccupancy.length" class="d-flex flex-column ga-3">
                <div v-for="clinic in data.clinicOccupancy" :key="clinic.id">
                  <div class="d-flex justify-space-between align-center mb-1">
                    <div>
                      <span class="text-body-2 font-weight-medium">{{ clinic.name }}</span>
                      <span class="text-caption text-medium-emphasis ml-1">· {{ clinic.city }}</span>
                    </div>
                    <span class="text-body-2 font-weight-bold">{{ clinic.occupancyRate }}%</span>
                  </div>
                  <v-progress-linear
                    :model-value="clinic.occupancyRate"
                    :color="clinic.occupancyRate >= 80 ? 'error' : clinic.occupancyRate >= 50 ? 'warning' : 'success'"
                    bg-color="surface-variant"
                    rounded
                    height="8"
                  />
                  <div class="text-caption text-medium-emphasis mt-1">
                    {{ clinic.bookedSlots }} / {{ clinic.totalSlots }} {{ t('admin.analytics.clinicOccupancy.slots') }}
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-medium-emphasis py-6">{{ t('admin.analytics.noData') }}</div>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card rounded="lg" height="100%">
            <v-card-text class="pa-4">
              <div class="text-subtitle-1 font-weight-bold mb-3">
                <v-icon size="18" class="mr-1">mdi-star</v-icon>
                {{ t('admin.analytics.reviewScores.title') }}
              </div>

              <!-- Rating distribution -->
              <div v-if="data.reviewScores.distribution && Object.keys(data.reviewScores.distribution).length" class="mb-4">
                <div class="text-caption text-medium-emphasis font-weight-medium mb-2 text-uppercase">
                  {{ t('admin.analytics.reviewScores.distribution') }}
                </div>
                <div v-for="key in ratingKeys" :key="key" class="d-flex align-center ga-2 mb-1">
                  <span class="text-caption rating-label">{{ starLabel(key) }}</span>
                  <v-progress-linear
                    :model-value="((data.reviewScores.distribution[key] ?? 0) / ratingTotal(data.reviewScores.distribution)) * 100"
                    color="warning"
                    bg-color="surface-variant"
                    rounded
                    height="6"
                    class="flex-1-1"
                  />
                  <span class="text-caption text-medium-emphasis rating-count">{{ data.reviewScores.distribution[key] ?? 0 }}</span>
                </div>
              </div>

              <!-- Top rated doctors -->
              <div v-if="data.reviewScores.averageByDoctor?.length">
                <div class="text-caption text-medium-emphasis font-weight-medium mb-2 text-uppercase">
                  {{ t('admin.analytics.reviewScores.topRated') }}
                </div>
                <div class="ranking-list">
                  <div
                    v-for="doc in data.reviewScores.averageByDoctor.slice(0, 5)"
                    :key="doc.id"
                    class="ranking-item"
                  >
                    <div class="rank-info">
                      <div class="text-body-2 font-weight-medium">{{ doc.name }}</div>
                      <v-rating
                        :model-value="doc.averageRating"
                        readonly
                        half-increments
                        density="compact"
                        size="x-small"
                        color="warning"
                        active-color="warning"
                      />
                    </div>
                    <div class="rank-meta">
                      <span class="text-body-2 font-weight-bold">{{ doc.averageRating }}</span>
                      <span class="text-caption text-medium-emphasis ml-1">({{ doc.reviewCount }})</span>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="!Object.keys(data.reviewScores.distribution ?? {}).length && !data.reviewScores.averageByDoctor?.length"
                   class="text-center text-medium-emphasis py-6">
                {{ t('admin.analytics.noData') }}
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
      <!-- ── Revenue ──────────────────────────────────────────────────────── -->
      <template v-if="data.revenue">
        <!-- Revenue KPI cards -->
        <v-row dense class="mt-2 mb-2">
          <v-col cols="12" sm="6">
            <v-card variant="tonal" color="teal" rounded="lg">
              <v-card-text class="pa-4">
                <div class="d-flex align-center justify-space-between mb-2">
                  <span class="text-caption font-weight-medium text-uppercase">{{ t('admin.analytics.revenue.totalRevenue') }}</span>
                  <v-icon size="20" color="teal">mdi-currency-usd</v-icon>
                </div>
                <div class="text-h4 font-weight-bold">
                  {{ data.revenue.totalRevenue.toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  <span class="text-h6">{{ t('admin.analytics.revenue.ron') }}</span>
                </div>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" sm="6">
            <v-card variant="tonal" color="cyan" rounded="lg">
              <v-card-text class="pa-4">
                <div class="d-flex align-center justify-space-between mb-2">
                  <span class="text-caption font-weight-medium text-uppercase">{{ t('admin.analytics.revenue.avgRevenue') }}</span>
                  <v-icon size="20" color="cyan">mdi-chart-line</v-icon>
                </div>
                <div class="text-h4 font-weight-bold">
                  {{ data.revenue.avgRevenue.toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  <span class="text-h6">{{ t('admin.analytics.revenue.ron') }}</span>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Revenue chart -->
        <v-card rounded="lg" class="mb-4">
          <v-card-text class="pa-4">
            <div class="text-subtitle-1 font-weight-bold mb-4">{{ t('admin.analytics.revenue.chart') }}</div>
            <div v-if="data.revenue.revenueByPeriod.length" class="bar-chart-wrap">
              <div class="bar-chart">
                <div
                  v-for="item in data.revenue.revenueByPeriod"
                  :key="item.label"
                  class="bar-col"
                >
                  <v-tooltip :text="`${item.revenue.toLocaleString('ro-RO', { minimumFractionDigits: 2 })} RON`" location="top">
                    <template #activator="{ props }">
                      <div v-bind="props" class="bar-stack">
                        <div
                          class="bar-segment bar-revenue"
                          :style="{ height: revBarPct(item.revenue) + '%' }"
                        ></div>
                      </div>
                    </template>
                  </v-tooltip>
                  <div class="bar-label">{{ formatChartLabel(item.label) }}</div>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-medium-emphasis py-8">
              {{ t('admin.analytics.revenue.noData') }}
            </div>
          </v-card-text>
        </v-card>

        <!-- Top by revenue -->
        <v-row dense class="mb-4">
          <v-col cols="12" md="6">
            <v-card rounded="lg" height="100%">
              <v-card-text class="pa-4">
                <div class="text-subtitle-1 font-weight-bold mb-3">
                  <v-icon size="18" class="mr-1">mdi-doctor</v-icon>
                  {{ t('admin.analytics.revenue.topByDoctor') }}
                </div>
                <div v-if="data.revenue.topDoctors.length" class="ranking-list">
                  <div
                    v-for="(doc, i) in data.revenue.topDoctors"
                    :key="doc.id"
                    class="ranking-item"
                  >
                    <span class="rank-num text-medium-emphasis">{{ i + 1 }}</span>
                    <div class="rank-info">
                      <div class="text-body-2 font-weight-medium">{{ doc.name }}</div>
                      <v-progress-linear
                        :model-value="revBarPct(doc.revenue)"
                        color="teal"
                        bg-color="transparent"
                        rounded
                        height="4"
                        class="mt-1"
                      />
                    </div>
                    <div class="rank-meta">
                      <span class="text-body-2 font-weight-bold">{{ doc.revenue.toLocaleString('ro-RO', { minimumFractionDigits: 0 }) }}</span>
                      <span class="text-caption text-medium-emphasis ml-1">RON</span>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center text-medium-emphasis py-6">{{ t('admin.analytics.revenue.noData') }}</div>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="6">
            <v-card rounded="lg" height="100%">
              <v-card-text class="pa-4">
                <div class="text-subtitle-1 font-weight-bold mb-3">
                  <v-icon size="18" class="mr-1">mdi-stethoscope</v-icon>
                  {{ t('admin.analytics.revenue.topByService') }}
                </div>
                <div v-if="data.revenue.topServices.length" class="ranking-list">
                  <div
                    v-for="(svc, i) in data.revenue.topServices"
                    :key="svc.name"
                    class="ranking-item"
                  >
                    <span class="rank-num text-medium-emphasis">{{ i + 1 }}</span>
                    <div class="rank-info">
                      <div class="text-body-2 font-weight-medium">{{ svc.name }}</div>
                      <v-progress-linear
                        :model-value="revBarPct(svc.revenue)"
                        color="cyan"
                        bg-color="transparent"
                        rounded
                        height="4"
                        class="mt-1"
                      />
                    </div>
                    <div class="rank-meta">
                      <span class="text-body-2 font-weight-bold">{{ svc.revenue.toLocaleString('ro-RO', { minimumFractionDigits: 0 }) }}</span>
                      <span class="text-caption text-medium-emphasis ml-1">RON</span>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center text-medium-emphasis py-6">{{ t('admin.analytics.revenue.noData') }}</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </template>
    </template>
  </div>
</template>

<style scoped>
/* ── Bar chart ─────────────────────────────────────────────────────────────── */
.bar-chart-wrap {
  overflow-x: auto;
}

.bar-chart {
  display: flex;
  align-items: flex-end;
  gap: 4px;
  min-width: max-content;
}

.bar-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  min-width: 28px;
  max-width: 56px;
  gap: 4px;
}

.bar-stack {
  display: flex;
  flex-direction: column-reverse;
  width: 100%;
  height: 140px;
  gap: 1px;
  cursor: default;
}

.bar-segment {
  width: 100%;
  border-radius: 3px 3px 0 0;
  min-height: 2px;
  transition: height 0.3s ease;
}

.bar-booked {
  background: rgb(var(--v-theme-primary));
  opacity: 0.85;
}

.bar-cancelled {
  background: rgb(var(--v-theme-error));
  opacity: 0.7;
  border-radius: 0;
}

.bar-revenue {
  background: rgb(var(--v-theme-teal));
  opacity: 0.85;
  border-radius: 3px 3px 0 0;
}

.bar-booked:first-child {
  border-radius: 3px 3px 0 0;
}

.bar-label {
  font-size: 0.7rem;
  color: rgba(var(--v-theme-on-surface), 0.75);
  white-space: nowrap;
  text-align: center;
  width: 100%;
  overflow: hidden;
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 2px;
}

/* ── Rankings ──────────────────────────────────────────────────────────────── */
.ranking-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.ranking-item {
  display: flex;
  align-items: center;
  gap: 10px;
}

.rank-num {
  font-size: 0.75rem;
  font-weight: 700;
  min-width: 18px;
  text-align: right;
}

.rank-info {
  flex: 1;
  min-width: 0;
}

.rank-meta {
  display: flex;
  align-items: center;
  white-space: nowrap;
}

/* ── Review ────────────────────────────────────────────────────────────────── */
.rating-label {
  min-width: 32px;
  text-align: right;
  color: rgb(var(--v-theme-warning));
  font-size: 0.75rem;
  white-space: nowrap;
}

.rating-count {
  min-width: 24px;
  text-align: right;
}
</style>
