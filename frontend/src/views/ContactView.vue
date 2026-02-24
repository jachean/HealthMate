<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import api from '@/lib/api'
import ClinicMap from '@/components/ui/ClinicMap.vue'

const { t } = useI18n()
const auth = useAuthStore()

const isLoggedIn = computed(() => !!auth.user)

const OFFICE = {
  address: 'Calea București 32',
  city: 'Craiova',
  email: 'jachean.mihai.y6b@student.ucv.ro',
  phone: '+40 251 412 600',
  hours: 'Lun–Vin / Mon–Fri, 08:00–17:00',
}

const name = ref('')
const email = ref('')
const subject = ref('')
const message = ref('')
const submitting = ref(false)
const submitted = ref(false)
const submitError = ref(false)

const senderName = computed(() =>
  isLoggedIn.value ? `${auth.user.firstName} ${auth.user.lastName}` : name.value
)
const senderEmail = computed(() =>
  isLoggedIn.value ? auth.user.email : email.value
)

const canSubmit = computed(() =>
  senderName.value && senderEmail.value && message.value && !submitting.value
)

async function sendMessage() {
  if (!canSubmit.value) return
  submitting.value = true
  submitError.value = false

  try {
    await api.post('/api/contact', {
      name: senderName.value,
      email: senderEmail.value,
      subject: subject.value,
      message: message.value,
    })
    submitted.value = true
  } catch {
    submitError.value = true
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <!-- ── Hero ─────────────────────────────────────────────────────────────── -->
  <div class="contact-hero">
    <v-container max-width="960" class="hero-inner px-4">
      <v-chip size="small" color="primary" variant="tonal" class="mb-4">
        <v-icon start size="14">mdi-email-heart-outline</v-icon>
        {{ t('contact.chip') }}
      </v-chip>
      <h1 class="contact-title mb-3">{{ t('contact.title') }}</h1>
      <p class="contact-subtitle text-medium-emphasis">{{ t('contact.subtitle') }}</p>
    </v-container>
  </div>

  <!-- ── Main content ───────────────────────────────────────────────────────── -->
  <v-container max-width="960" class="py-10 px-4">
    <v-row>
      <!-- Info cards -->
      <v-col cols="12" md="4">
        <div class="d-flex flex-column ga-4">

          <v-card flat rounded="xl" class="info-card pa-5">
            <div class="d-flex align-start ga-4">
              <div class="info-icon-wrap">
                <v-icon size="20" color="primary">mdi-email-outline</v-icon>
              </div>
              <div class="overflow-hidden">
                <div class="info-label">{{ t('contact.email') }}</div>
                <a :href="`mailto:${OFFICE.email}`" class="info-value info-link text-truncate d-block">
                  {{ OFFICE.email }}
                </a>
                <div class="info-hint">{{ t('contact.emailHint') }}</div>
              </div>
            </div>
          </v-card>

          <v-card flat rounded="xl" class="info-card pa-5">
            <div class="d-flex align-start ga-4">
              <div class="info-icon-wrap">
                <v-icon size="20" color="primary">mdi-phone-outline</v-icon>
              </div>
              <div>
                <div class="info-label">{{ t('contact.phone') }}</div>
                <a :href="`tel:${OFFICE.phone}`" class="info-value info-link">{{ OFFICE.phone }}</a>
                <div class="info-hint">{{ OFFICE.hours }}</div>
              </div>
            </div>
          </v-card>

          <v-card flat rounded="xl" class="info-card pa-5">
            <div class="d-flex align-start ga-4">
              <div class="info-icon-wrap">
                <v-icon size="20" color="primary">mdi-map-marker-outline</v-icon>
              </div>
              <div>
                <div class="info-label">{{ t('contact.address') }}</div>
                <div class="info-value">{{ OFFICE.address }}</div>
                <div class="info-hint">{{ OFFICE.city }}, Dolj</div>
              </div>
            </div>
          </v-card>

        </div>
      </v-col>

      <!-- Form card -->
      <v-col cols="12" md="8">
        <v-card flat rounded="xl" class="form-card pa-6">

          <!-- Success -->
          <div v-if="submitted" class="d-flex flex-column align-center py-10 text-center">
            <div class="success-icon-wrap mb-5">
              <v-icon size="40" color="success">mdi-check-bold</v-icon>
            </div>
            <div class="text-h6 font-weight-bold mb-2">{{ t('contact.successTitle') }}</div>
            <div class="text-body-2 text-medium-emphasis">{{ t('contact.successText') }}</div>
          </div>

          <!-- Form -->
          <template v-else>
            <div class="text-subtitle-1 font-weight-bold mb-5">{{ t('contact.formTitle') }}</div>

            <!-- Logged-in sender info -->
            <div v-if="isLoggedIn" class="sender-info mb-4">
              <v-icon size="16" color="medium-emphasis" class="mr-2">mdi-account-circle-outline</v-icon>
              <span class="text-body-2 text-medium-emphasis">{{ t('contact.sendingAs') }}</span>
              <span class="text-body-2 font-weight-semibold ml-1">{{ senderName }}</span>
              <span class="text-body-2 text-medium-emphasis ml-1">({{ senderEmail }})</span>
            </div>

            <!-- Guest name + email fields -->
            <v-row v-else dense class="mb-1">
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="name"
                  :label="t('contact.name')"
                  variant="outlined"
                  rounded="lg"
                  density="compact"
                  prepend-inner-icon="mdi-account-outline"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="email"
                  :label="t('contact.yourEmail')"
                  variant="outlined"
                  rounded="lg"
                  density="compact"
                  prepend-inner-icon="mdi-email-outline"
                  type="email"
                />
              </v-col>
            </v-row>

            <v-text-field
              v-model="subject"
              :label="t('contact.subject')"
              variant="outlined"
              rounded="lg"
              density="compact"
              prepend-inner-icon="mdi-text-short"
              class="mb-1"
            />

            <v-textarea
              v-model="message"
              :label="t('contact.message')"
              :placeholder="t('contact.messagePlaceholder')"
              variant="outlined"
              rounded="lg"
              density="compact"
              rows="5"
              auto-grow
              :maxlength="1000"
              counter
              class="mb-4"
            />

            <v-alert
              v-if="submitError"
              type="error"
              variant="tonal"
              density="compact"
              rounded="lg"
              class="mb-4"
            >
              {{ t('contact.errorText') }}
            </v-alert>

            <v-btn
              color="primary"
              block
              rounded="lg"
              size="large"
              :loading="submitting"
              :disabled="!canSubmit"
              prepend-icon="mdi-send-outline"
              @click="sendMessage"
            >
              {{ t('contact.send') }}
            </v-btn>
          </template>

        </v-card>
      </v-col>
    </v-row>
  </v-container>

  <!-- ── Map ───────────────────────────────────────────────────────────────── -->
  <v-container max-width="960" class="pb-12 px-4">
    <div class="map-wrapper">
      <ClinicMap
        :address="OFFICE.address"
        :city="OFFICE.city"
        :lat="44.3150"
        :lng="23.8020"
        clinic-name="HealthMate"
      />
    </div>
  </v-container>
</template>

<style scoped>
/* ── Hero ─────────────────────────────────────────────────────────────────── */
.contact-hero {
  background: linear-gradient(
    160deg,
    rgba(var(--v-theme-primary), 0.05) 0%,
    rgba(var(--v-theme-surface), 1) 70%
  );
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}

.hero-inner {
  padding-top: 56px;
  padding-bottom: 56px;
}

.contact-title {
  font-size: 2.2rem;
  font-weight: 800;
  letter-spacing: -0.5px;
  line-height: 1.2;
  color: rgb(var(--v-theme-on-surface));
}

.contact-subtitle {
  font-size: 1rem;
  max-width: 520px;
  line-height: 1.6;
}

/* ── Info cards ───────────────────────────────────────────────────────────── */
.info-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.07);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.info-card:hover {
  border-color: rgba(var(--v-theme-primary), 0.25);
  box-shadow: 0 4px 16px rgba(var(--v-theme-primary), 0.06) !important;
}

.info-icon-wrap {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: rgba(var(--v-theme-primary), 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.info-label {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.7px;
  color: rgba(var(--v-theme-on-surface), 0.45);
  margin-bottom: 3px;
}

.info-value {
  font-size: 14px;
  font-weight: 600;
  color: rgb(var(--v-theme-on-surface));
  line-height: 1.3;
}

.info-link {
  color: rgb(var(--v-theme-primary));
  text-decoration: none;
}

.info-link:hover {
  text-decoration: underline;
}

.info-hint {
  font-size: 11px;
  color: rgba(var(--v-theme-on-surface), 0.45);
  margin-top: 3px;
}

/* ── Form card ────────────────────────────────────────────────────────────── */
.form-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.07);
}

/* ── Success icon ─────────────────────────────────────────────────────────── */
.success-icon-wrap {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(var(--v-theme-success), 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ── Sender info ──────────────────────────────────────────────────────────── */
.sender-info {
  display: flex;
  align-items: center;
  padding: 10px 14px;
  background: rgba(var(--v-theme-primary), 0.05);
  border-radius: 10px;
  border: 1px solid rgba(var(--v-theme-primary), 0.12);
}

/* ── Map wrapper ──────────────────────────────────────────────────────────── */
.map-wrapper {
  height: 300px;
  border-radius: 24px;
  overflow: hidden;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.07);
  display: flex;
  flex-direction: column;
}
</style>
