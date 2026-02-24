import { useI18n } from 'vue-i18n'

export const useAuthValidation = (passwordRef = null) => {
  const { t } = useI18n()

  const loginRules = {
    email: [
      (v) => !!v || t('auth.validation.required', { field: t('auth.validation.fieldEmail') }),
      (v) => !v || /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/.test(v) || t('auth.validation.emailInvalid'),
    ],
    password: [
      (v) => !!v || t('auth.validation.required', { field: t('auth.validation.fieldPassword') }),
    ],
  }

  const registerRules = {
    firstName: [
      (v) => !!v || t('auth.validation.required', { field: t('auth.validation.fieldFirstName') }),
    ],
    lastName: [
      (v) => !!v || t('auth.validation.required', { field: t('auth.validation.fieldLastName') }),
    ],
    email: [
      (v) => !!v || t('auth.validation.required', { field: t('auth.validation.fieldEmail') }),
      (v) => !v || /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/.test(v) || t('auth.validation.emailInvalid'),
    ],
    username: [
      (v) => !!v || t('auth.validation.required', { field: t('auth.validation.fieldUsername') }),
    ],
    password: [
      (v) => !!v || t('auth.validation.required', { field: t('auth.validation.fieldPassword') }),
      (v) => !v || v.length >= 8 || t('auth.validation.passwordMinLength', { min: 8 }),
      (v) => !v || /[A-Z]/.test(v) || t('auth.validation.passwordUppercase'),
      (v) => !v || /\d/.test(v) || t('auth.validation.passwordDigit'),
    ],
  }

  if (passwordRef) {
    registerRules.confirmPassword = [
      (v) => !!v || t('auth.validation.required', { field: t('auth.validation.fieldConfirmPassword') }),
      (v) => v === passwordRef.value || t('auth.validation.passwordsMatch'),
    ]
  }

  return { loginRules, registerRules }
}
