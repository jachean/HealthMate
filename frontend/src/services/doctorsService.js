import api from '@/lib/api'

export function getDoctors(params = {}) {
  return api
    .get('/api/doctors', { params })
    .then(res => Array.isArray(res.data) ? res.data : [])
}
