<script setup>
const LAST_UPDATED = 'February 24, 2026'
const sections = [
  {
    icon: 'mdi-file-sign',
    title: 'Acceptance of Terms',
    body: `By accessing or using HealthMate, you confirm that you are at least 16 years old and agree to be bound by these Terms of Service. If you do not agree with any part of these terms, you may not use our platform. We reserve the right to update these terms at any time — continued use of the platform after changes constitutes acceptance.`,
  },
  {
    icon: 'mdi-account-circle-outline',
    title: 'Your Account',
    body: `You are responsible for maintaining the confidentiality of your account credentials and for all activity that occurs under your account. You must provide accurate and complete information when registering. HealthMate reserves the right to suspend or terminate accounts that violate these terms or that are used for fraudulent purposes.`,
  },
  {
    icon: 'mdi-calendar-check-outline',
    title: 'Booking & Appointments',
    body: `HealthMate is a scheduling platform that connects patients with healthcare professionals. We do not provide medical advice, diagnosis, or treatment. Appointments booked through HealthMate are agreements between you and the healthcare provider. We are not responsible for the quality, outcome, or cancellation of medical services provided by listed doctors or clinics.`,
  },
  {
    icon: 'mdi-shield-alert-outline',
    title: 'Medical Disclaimer',
    body: `Content available on HealthMate, including doctor profiles, service descriptions, and any other material, is for informational purposes only. It does not constitute professional medical advice. Always seek the guidance of a qualified healthcare provider with any questions you may have regarding a medical condition. Never disregard professional advice based on information found on this platform.`,
  },
  {
    icon: 'mdi-star-outline',
    title: 'Reviews & User Content',
    body: `By submitting a review or any other content on HealthMate, you grant us a non-exclusive, royalty-free license to display that content on the platform. You are solely responsible for the content you submit. Reviews must be honest, based on genuine experience, and must not contain defamatory, offensive, or misleading information. We reserve the right to remove content that violates these guidelines.`,
  },
  {
    icon: 'mdi-lock-outline',
    title: 'Intellectual Property',
    body: `All content on HealthMate — including the logo, design, text, graphics, and code — is the property of HealthMate and is protected by applicable intellectual property laws. You may not reproduce, distribute, or create derivative works without our explicit written consent.`,
  },
  {
    icon: 'mdi-alert-circle-outline',
    title: 'Limitation of Liability',
    body: `To the fullest extent permitted by law, HealthMate shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of the platform. Our total liability for any claim arising out of or relating to these terms shall not exceed the amount you paid us, if any, in the twelve months prior to the claim.`,
  },
  {
    icon: 'mdi-gavel',
    title: 'Governing Law',
    body: `These Terms of Service are governed by and construed in accordance with the laws of Romania. Any disputes arising under or in connection with these terms shall be subject to the exclusive jurisdiction of the courts of Craiova, Dolj County, Romania.`,
  },
]
</script>

<template>
  <!-- Hero -->
  <div class="legal-hero">
    <v-container max-width="800" class="hero-inner px-4">
      <v-chip size="small" color="primary" variant="tonal" class="mb-4">
        <v-icon start size="14">mdi-file-document-outline</v-icon>
        Legal
      </v-chip>
      <h1 class="legal-title mb-3">Terms of Service</h1>
      <p class="legal-subtitle text-medium-emphasis">
        Please read these terms carefully before using HealthMate.
      </p>
      <div class="d-flex align-center ga-2 mt-4">
        <v-icon size="14" color="medium-emphasis">mdi-calendar-outline</v-icon>
        <span class="text-caption text-medium-emphasis">Last updated: {{ LAST_UPDATED }}</span>
      </div>
    </v-container>
  </div>

  <!-- Content -->
  <v-container max-width="800" class="py-10 px-4">

    <!-- Intro card -->
    <v-card flat rounded="xl" class="intro-card pa-6 mb-8">
      <div class="d-flex align-start ga-4">
        <div class="intro-icon-wrap flex-shrink-0">
          <v-icon size="22" color="primary">mdi-information-outline</v-icon>
        </div>
        <div class="text-body-2" style="line-height:1.75;">
          Welcome to <strong>HealthMate</strong> — a medical appointment booking platform developed as
          an academic project at the University of Craiova. These terms govern your use of our platform
          and services. By creating an account or booking an appointment, you agree to these terms.
        </div>
      </div>
    </v-card>

    <!-- Sections -->
    <div class="sections-list">
      <div
        v-for="(section, i) in sections"
        :key="section.title"
        class="section-item"
      >
        <!-- Number + icon row -->
        <div class="d-flex align-center ga-3 mb-3">
          <div class="section-number">{{ String(i + 1).padStart(2, '0') }}</div>
          <div class="section-icon-wrap">
            <v-icon size="18" color="primary">{{ section.icon }}</v-icon>
          </div>
          <h2 class="section-title">{{ section.title }}</h2>
        </div>

        <p class="section-body text-body-2">{{ section.body }}</p>

        <v-divider v-if="i < sections.length - 1" class="mt-6" />
      </div>
    </div>

    <!-- Contact footer -->
    <v-card flat rounded="xl" class="contact-card pa-6 mt-10 text-center">
      <v-icon size="32" color="primary" class="mb-3">mdi-email-outline</v-icon>
      <div class="text-subtitle-2 font-weight-bold mb-1">Questions about these terms?</div>
      <div class="text-body-2 text-medium-emphasis mb-4">
        Reach out and we'll be happy to clarify anything.
      </div>
      <v-btn
        color="primary"
        variant="tonal"
        rounded="lg"
        :to="{ name: 'contact' }"
        prepend-icon="mdi-arrow-right"
      >
        Contact us
      </v-btn>
    </v-card>

  </v-container>
</template>

<style scoped>
.legal-hero {
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
  background: rgba(var(--v-theme-primary), 0.04);
  border: 1px solid rgba(var(--v-theme-primary), 0.12);
}

.intro-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(var(--v-theme-primary), 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Sections */
.sections-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.section-item {
  padding: 28px 0;
}

.section-item:first-child {
  padding-top: 0;
}

.section-number {
  font-size: 11px;
  font-weight: 800;
  color: rgba(var(--v-theme-primary), 0.4);
  letter-spacing: 0.5px;
  min-width: 24px;
}

.section-icon-wrap {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  background: rgba(var(--v-theme-primary), 0.08);
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

.section-body {
  color: rgba(var(--v-theme-on-surface), 0.7);
  line-height: 1.8;
  padding-left: 60px;
  margin: 0;
}

/* Contact card */
.contact-card {
  border: 1.5px dashed rgba(var(--v-theme-primary), 0.2);
  background: rgba(var(--v-theme-primary), 0.02);
}

@media (max-width: 599px) {
  .legal-title {
    font-size: 1.8rem;
  }

  .section-body {
    padding-left: 0;
  }
}
</style>
