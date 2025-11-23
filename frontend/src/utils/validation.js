export const required = (fieldName) => (v) =>
  !!v || `${fieldName} is required`

export const emailRule = (v) =>
  !v || /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v) || 'Email must be valid'

export const minPasswordLength = (min) => (v) =>
  !v || v.length >= min || `Password must be at least ${min} characters`

export const passwordHasUppercase = (v) =>
  !v || /[A-Z]/.test(v) || 'Password must contain at least one uppercase letter'

export const passwordHasDigit = (v) =>
  !v || /\d/.test(v) || 'Password must contain at least one digit'
