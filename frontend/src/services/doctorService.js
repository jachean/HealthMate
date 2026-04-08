import api from '@/lib/api'

export function doctorGetAppointments(date) {
  return api.get('/api/doctor/appointments', { params: date ? { date } : {} }).then(r => r.data)
}

export function doctorStartAppointment(id) {
  return api.patch(`/api/doctor/appointments/${id}/start`).then(r => r.data)
}

export function doctorCompleteAppointment(id) {
  return api.patch(`/api/doctor/appointments/${id}/complete`).then(r => r.data)
}

export function doctorCancelAppointment(id) {
  return api.patch(`/api/doctor/appointments/${id}/cancel`).then(r => r.data)
}

export function doctorPauseAppointment(id) {
  return api.patch(`/api/doctor/appointments/${id}/pause`).then(r => r.data)
}

export function doctorReopenAppointment(id) {
  return api.patch(`/api/doctor/appointments/${id}/reopen`).then(r => r.data)
}
