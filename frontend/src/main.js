import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'


import '@mdi/font/css/materialdesignicons.css'
import 'flag-icons/css/flag-icons.min.css'
import { aliases, mdi } from 'vuetify/iconsets/mdi'

import i18n from './i18n'
import './assets/main.css'

const vuetify = createVuetify({
  components,
  directives,
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: { mdi },
  },
  theme: {
    defaultTheme: 'healthmate',
    themes: {
      healthmate: {
        dark: false,
        colors: {
          primary: '#1565C0',
          'primary-darken-1': '#0D47A1',
          secondary: '#00897B',
          'secondary-darken-1': '#00695C',
          accent: '#26A69A',
          background: '#FAFBFD',
          surface: '#FFFFFF',
          'surface-variant': '#F0F4F8',
          error: '#D32F2F',
          warning: '#F9A825',
          info: '#1565C0',
          success: '#2E7D32',
          'on-primary': '#FFFFFF',
          'on-secondary': '#FFFFFF',
          'on-background': '#1A2138',
          'on-surface': '#1A2138',
        },
      },
    },
  },
  defaults: {
    VBtn: { rounded: 'lg' },
    VCard: { rounded: 'xl' },
    VChip: { rounded: 'lg' },
  },
})

createApp(App)
  .use(createPinia())
  .use(router)
  .use(vuetify)
  .use(i18n)
  .mount('#app')
