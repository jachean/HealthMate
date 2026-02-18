import { createI18n } from 'vue-i18n'
import en from './en'
import ro from './ro'

const savedLocale = localStorage.getItem('hm_locale') || 'en'

const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'en',
  messages: { en, ro },
})

export default i18n
