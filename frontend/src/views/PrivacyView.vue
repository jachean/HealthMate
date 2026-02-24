<script setup>
const LAST_UPDATED = 'February 24, 2026'

const sections = [
  {
    icon: 'mdi-database-outline',
    title: 'What Data We Collect',
    items: [
      { label: 'Account data', text: 'When you register, we collect your first name, last name, email address, and username.' },
      { label: 'Appointment data', text: 'When you book an appointment, we store the selected doctor, clinic, service, time slot, and price.' },
      { label: 'Review data', text: 'If you submit a review, we store your rating, comment, and a shortened version of your name for display.' },
      { label: 'Contact messages', text: 'If you contact us via the contact form, we store your name, email, subject, and message.' },
      { label: 'Usage data', text: 'We do not use third-party analytics or tracking. No cookies beyond what is strictly necessary for authentication are used.' },
    ],
  },
  {
    icon: 'mdi-cog-outline',
    title: 'How We Use Your Data',
    items: [
      { label: 'Authentication', text: 'Your email and password (hashed — never stored in plain text) are used to identify you on the platform.' },
      { label: 'Appointment management', text: 'Booking data is used to display your appointment history in your profile.' },
      { label: 'Transactional emails', text: 'We send emails upon registration and when you contact us. We do not send marketing emails.' },
      { label: 'Platform improvement', text: 'We may use anonymised, aggregated data to understand how the platform is used and improve it.' },
    ],
  },
  {
    icon: 'mdi-share-outline',
    title: 'Data Sharing',
    items: [
      { label: 'Healthcare providers', text: 'Your booking details are visible to the doctor and clinic involved in your appointment.' },
      { label: 'No third-party selling', text: 'We never sell your personal data to third parties.' },
      { label: 'Legal requirements', text: 'We may disclose data if required by law or to protect the rights, property, or safety of HealthMate and its users.' },
    ],
  },
  {
    icon: 'mdi-lock-check-outline',
    title: 'Data Security',
    items: [
      { label: 'Passwords', text: 'Passwords are hashed using industry-standard algorithms and are never stored or transmitted in plain text.' },
      { label: 'Authentication tokens', text: 'We use signed JWT tokens for session management. Tokens are short-lived and validated on every request.' },
      { label: 'Transport encryption', text: 'All communication between your browser and our servers is encrypted via HTTPS.' },
    ],
  },
  {
    icon: 'mdi-account-check-outline',
    title: 'Your Rights',
    items: [
      { label: 'Access', text: 'You can view your personal data at any time from your profile page.' },
      { label: 'Correction', text: 'If your data is inaccurate, contact us and we will correct it promptly.' },
      { label: 'Deletion', text: 'You may request deletion of your account and associated data by contacting our support team.' },
      { label: 'Portability', text: 'You may request an export of your personal data in a machine-readable format.' },
    ],
  },
  {
    icon: 'mdi-cookie-outline',
    title: 'Cookies',
    items: [
      { label: 'Essential only', text: 'We only use cookies that are strictly necessary for the platform to function — specifically, to store your authentication state in local storage.' },
      { label: 'No tracking cookies', text: 'We do not use advertising, analytics, or third-party tracking cookies of any kind.' },
    ],
  },
  {
    icon: 'mdi-refresh',
    title: 'Changes to This Policy',
    items: [
      { label: 'Notification', text: 'If we make significant changes to this Privacy Policy, we will notify you via email or a prominent notice on the platform.' },
      { label: 'Continued use', text: 'Your continued use of HealthMate after changes are posted constitutes your acceptance of the updated policy.' },
    ],
  },
]
</script>

<template>
  <!-- Hero -->
  <div class="legal-hero">
    <v-container max-width="800" class="hero-inner px-4">
      <v-chip size="small" color="secondary" variant="tonal" class="mb-4">
        <v-icon start size="14">mdi-shield-check-outline</v-icon>
        Privacy
      </v-chip>
      <h1 class="legal-title mb-3">Privacy Policy</h1>
      <p class="legal-subtitle text-medium-emphasis">
        We take your privacy seriously. Here's exactly what we collect, why, and how we protect it.
      </p>
      <div class="d-flex align-center ga-2 mt-4">
        <v-icon size="14" color="medium-emphasis">mdi-calendar-outline</v-icon>
        <span class="text-caption text-medium-emphasis">Last updated: {{ LAST_UPDATED }}</span>
      </div>
    </v-container>
  </div>

  <!-- Content -->
  <v-container max-width="800" class="py-10 px-4">

    <!-- GDPR badge -->
    <v-card flat rounded="xl" class="intro-card pa-6 mb-8">
      <div class="d-flex align-start ga-4">
        <div class="intro-icon-wrap flex-shrink-0">
          <v-icon size="22" color="secondary">mdi-shield-half-full</v-icon>
        </div>
        <div class="text-body-2" style="line-height:1.75;">
          HealthMate is committed to protecting your personal data in accordance with
          <strong>Regulation (EU) 2016/679 (GDPR)</strong> and Romanian data protection law.
          We collect only what is necessary, store it securely, and never sell it.
          If you have any questions, contact us at
          <a href="mailto:j234mediplant@gmail.com" class="privacy-link">j234mediplant@gmail.com</a>.
        </div>
      </div>
    </v-card>

    <!-- Sections -->
    <div class="sections-list">
      <div
        v-for="(section, si) in sections"
        :key="section.title"
        class="section-item"
      >
        <!-- Section header -->
        <div class="d-flex align-center ga-3 mb-4">
          <div class="section-icon-wrap">
            <v-icon size="18" color="secondary">{{ section.icon }}</v-icon>
          </div>
          <h2 class="section-title">{{ section.title }}</h2>
        </div>

        <!-- Items -->
        <div class="items-list">
          <div
            v-for="item in section.items"
            :key="item.label"
            class="privacy-item"
          >
            <div class="item-label">{{ item.label }}</div>
            <div class="item-text text-body-2">{{ item.text }}</div>
          </div>
        </div>

        <v-divider v-if="si < sections.length - 1" class="mt-6" />
      </div>
    </div>

    <!-- Bottom cards row -->
    <v-row class="mt-10" dense>
      <v-col cols="12" sm="6">
        <v-card flat rounded="xl" class="bottom-card pa-5 text-center h-100">
          <v-icon size="28" color="secondary" class="mb-3">mdi-email-outline</v-icon>
          <div class="text-subtitle-2 font-weight-bold mb-1">Contact our team</div>
          <div class="text-body-2 text-medium-emphasis mb-4">
            Questions about how we handle your data?
          </div>
          <v-btn
            color="secondary"
            variant="tonal"
            rounded="lg"
            size="small"
            :to="{ name: 'contact' }"
          >
            Get in touch
          </v-btn>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6">
        <v-card flat rounded="xl" class="bottom-card pa-5 text-center h-100">
          <v-icon size="28" color="primary" class="mb-3">mdi-file-document-outline</v-icon>
          <div class="text-subtitle-2 font-weight-bold mb-1">Terms of Service</div>
          <div class="text-body-2 text-medium-emphasis mb-4">
            Read the rules that govern your use of HealthMate.
          </div>
          <v-btn
            color="primary"
            variant="tonal"
            rounded="lg"
            size="small"
            :to="{ name: 'terms' }"
          >
            Read terms
          </v-btn>
        </v-card>
      </v-col>
    </v-row>

  </v-container>
</template>

<style scoped>
.legal-hero {
  background: linear-gradient(
    160deg,
    rgba(var(--v-theme-secondary), 0.05) 0%,
    rgba(var(--v-theme-surface), 1) 70%
  );
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}

.hero-inner {
  padding-top: 56px;
  padding-bottom: 56px;
}

.legal-title {
  font-size: 2.2rem;
  font-weight: 800;
  letter-spacing: -0.5px;
  line-height: 1.2;
  color: rgb(var(--v-theme-on-surface));
}

.legal-subtitle {
  font-size: 1rem;
  line-height: 1.6;
}

/* Intro card */
.intro-card {
  background: rgba(var(--v-theme-secondary), 0.04);
  border: 1px solid rgba(var(--v-theme-secondary), 0.15);
}

.intro-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(var(--v-theme-secondary), 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}

.privacy-link {
  color: rgb(var(--v-theme-secondary));
  text-decoration: none;
  font-weight: 600;
}

.privacy-link:hover {
  text-decoration: underline;
}

/* Sections */
.sections-list {
  display: flex;
  flex-direction: column;
}

.section-item {
  padding: 28px 0;
}

.section-item:first-child {
  padding-top: 0;
}

.section-icon-wrap {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(var(--v-theme-secondary), 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.section-title {
  font-size: 15px;
  font-weight: 700;
  color: rgb(var(--v-theme-on-surface));
  margin: 0;
}

/* Privacy items */
.items-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-left: 48px;
}

.privacy-item {
  display: flex;
  gap: 12px;
  align-items: baseline;
}

.item-label {
  font-size: 13px;
  font-weight: 700;
  color: rgb(var(--v-theme-on-surface));
  min-width: 160px;
  flex-shrink: 0;
}

.item-text {
  color: rgba(var(--v-theme-on-surface), 0.7);
  line-height: 1.7;
}

/* Bottom cards */
.bottom-card {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.07);
}

@media (max-width: 599px) {
  .legal-title {
    font-size: 1.8rem;
  }

  .items-list {
    padding-left: 0;
  }

  .privacy-item {
    flex-direction: column;
    gap: 4px;
  }

  .item-label {
    min-width: unset;
  }
}
</style>
