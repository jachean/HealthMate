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

const savedTheme = localStorage.getItem('hm_theme') || 'healthmate-dark'

const vuetify = createVuetify({
  components,
  directives,
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: { mdi },
  },
  theme: {
    defaultTheme: savedTheme,
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
      'healthmate-dark': {
        dark: true,
        colors: {
          primary: '#42A5F5',
          'primary-darken-1': '#1E88E5',
          secondary: '#26A69A',
          'secondary-darken-1': '#00897B',
          accent: '#26A69A',
          background: '#0F1422',
          surface: '#1A2138',
          'surface-variant': '#242D45',
          error: '#EF5350',
          warning: '#FFA726',
          info: '#42A5F5',
          success: '#66BB6A',
          'on-primary': '#FFFFFF',
          'on-secondary': '#FFFFFF',
          'on-background': '#CDD5F3',
          'on-surface': '#CDD5F3',
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
