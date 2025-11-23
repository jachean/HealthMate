import { useAuthStore } from '@/stores/auth'
import api from '@/lib/api'

export const useAuthService = () => {
  const auth = useAuthStore()

  const login = async (email, password) => {
    return await auth.login(email, password)
  }

  const register = async (data) => {
    try {
      const response = await api.post('/api/register', data)

      return {
        ok: true,
        user: response.data,
      }
    } catch (e) {
      console.error('Register error:', e.response?.data)

      if (e.response?.status === 400) {
        return {
          ok: false,
          validationErrors: e.response.data.errors || null,
        }
      }

      return { ok: false, validationErrors: null }
    }
  }

  return { login, register }
}
