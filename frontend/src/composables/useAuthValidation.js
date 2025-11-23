import {
  required,
  emailRule,
  minPasswordLength,
  passwordHasUppercase,
  passwordHasDigit,
} from '@/utils/validation'

export const useAuthValidation = (passwordRef = null) => {
  const loginRules = {
    email: [required('Email'), emailRule],
    password: [required('Password')],
  }

  const registerRules = {
    firstName: [required('First name')],
    lastName: [required('Last name')],
    email: [required('Email'), emailRule],
    username: [required('Username')],
    password: [
      required('Password'),
      minPasswordLength(8),
      passwordHasUppercase,
      passwordHasDigit,
    ],
  }

  if (passwordRef) {
    registerRules.confirmPassword = [
      required('Confirm password'),
      (v) => v === passwordRef.value || 'Passwords must match',
    ]
  }

  return {
    loginRules,
    registerRules,
  }
}
