import { useAuthStore } from '@/stores/auth'

export const useAuthService = () => {
  const auth = useAuthStore()

  const login = async (email, password) => {
    return await auth.login(email, password)
  }

  const register = async (data) => {
    await new Promise(r => setTimeout(r, 500))
    return true
  }

  return { login, register }
}
