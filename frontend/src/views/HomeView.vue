<script setup>
import HomeHero from '@/components/home/HomeHero.vue'
import HomeTrustStrip from '@/components/home/HomeTrustStrip.vue'
import PopularSpecialties from '@/components/home/PopularSpecialties.vue'
import HowItWorks from '@/components/home/HowItWorks.vue'
import HomeCta from '@/components/home/HomeCta.vue'
import { watch } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

watch(
  () => route.name,
  (name) => {
    if (name === 'specialties') {
      document.getElementById('specialties')?.scrollIntoView({ behavior: 'smooth' })
    }
    if (name === 'how-it-works') {
      document.getElementById('how-it-works')?.scrollIntoView({ behavior: 'smooth' })
    }
  },
  { immediate: true }
)
</script>

<template>
  <div>
    <section class="hero-section">
      <v-container>
        <HomeHero />
      </v-container>
    </section>

    <section class="trust-section">
      <v-container>
        <HomeTrustStrip />
      </v-container>
    </section>

    <section id="specialties" class="specialties-section">
      <v-container>
        <PopularSpecialties />
      </v-container>
    </section>

    <section id="how-it-works" class="how-section">
      <v-container>
        <HowItWorks />
      </v-container>
    </section>

    <section class="cta-section">
      <v-container>
        <HomeCta />
      </v-container>
    </section>
  </div>
</template>

<style scoped>
.hero-section {
  padding: 80px 0 64px;
  background: linear-gradient(
    170deg,
    rgb(var(--v-theme-surface)) 0%,
    rgba(var(--v-theme-primary), 0.04) 100%
  );
}

.trust-section {
  padding: 48px 0;
  background: rgb(var(--v-theme-surface-variant));
}

.specialties-section {
  padding: 64px 0;
  background: rgb(var(--v-theme-surface));
}

.how-section {
  padding: 64px 0;
  background: rgb(var(--v-theme-surface-variant));
}

.cta-section {
  padding: 72px 0;
  background: linear-gradient(
    135deg,
    rgb(var(--v-theme-primary)) 0%,
    rgb(var(--v-theme-primary-darken-1)) 100%
  );
}

@media (max-width: 959px) {
  .hero-section {
    padding: 40px 0 36px;
  }
  .trust-section {
    padding: 32px 0;
  }
  .specialties-section {
    padding: 40px 0;
  }
  .how-section {
    padding: 40px 0;
  }
  .cta-section {
    padding: 48px 0;
  }
}
</style>
