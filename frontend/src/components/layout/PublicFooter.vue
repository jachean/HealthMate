<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
  <footer class="footer px-6">
    <v-container>
      <div class="footer-top">
        <div class="footer-copy">
          <div class="footer-title">
            {{ t('footer.tagline') }}
          </div>
          <div class="footer-subtitle">
            {{ t('footer.description') }}
          </div>
        </div>

        <div class="footer-links">
          <RouterLink to="/terms">{{ t('footer.terms') }}</RouterLink>
          <RouterLink to="/privacy">{{ t('footer.privacy') }}</RouterLink>
          <RouterLink to="/contact">{{ t('footer.contact') }}</RouterLink>
        </div>
      </div>

      <hr class="footer-divider" />

      <div class="footer-bottom">
        <span>&copy; {{ new Date().getFullYear() }} HealthMate</span>
        <span class="footer-tagline">{{ t('footer.bottomTagline') }}</span>
      </div>
    </v-container>
  </footer>
</template>

<style scoped>
.footer {
  background: #1A2138;
  color: rgba(255, 255, 255, 0.85);
  padding: 48px 0 24px;
}

.footer-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 32px;
}

.footer-copy {
  max-width: 520px;
}

.footer-title {
  font-size: 0.95rem;
  font-weight: 600;
  letter-spacing: -0.2px;
  color: white;
}

.footer-subtitle {
  margin-top: 4px;
  font-size: 0.875rem;
  opacity: 0.6;
}

.footer-links {
  display: flex;
  gap: 20px;
  white-space: nowrap;
}

.footer-links a {
  text-decoration: none;
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.875rem;
  transition: color 0.2s ease;
}

.footer-links a:hover {
  color: white;
}

.footer-divider {
  border: none;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  margin: 24px 0;
}

.footer-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem;
  opacity: 0.5;
}

.footer-tagline {
  white-space: nowrap;
}

@media (max-width: 600px) {
  .footer {
    padding: 32px 0 20px;
  }
  .footer-top {
    flex-direction: column;
    text-align: center;
    gap: 20px;
  }
  .footer-copy {
    max-width: none;
  }
  .footer-links {
    justify-content: center;
    gap: 16px;
  }
  .footer-divider {
    margin: 20px 0;
  }
  .footer-bottom {
    flex-direction: column;
    gap: 6px;
    text-align: center;
  }
}
</style>
