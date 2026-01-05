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
  <v-container class="py-8">
    <HomeHero class="mb-6" />
    <HomeTrustStrip class="mb-6" />
    <PopularSpecialties class="mb-6" />
    <HowItWorks class="mb-6" />
    <HomeCta />
  </v-container>
</template>
