import api from '@/lib/api'

export async function createAppointment(timeSlotId) {
  const { data } = await api.post('/api/appointments', {
    timeSlotId,
  })

  return data
}
