import api from '@/lib/api'

export function adminGetDoctors({ page = 1, limit = 20, search } = {}) {
  return api.get('/api/admin/doctors', { params: { page, limit, search } }).then(r => r.data)
}

export function adminCreateDoctor(payload) {
  return api.post('/api/admin/doctors', payload).then(r => r.data)
}

export function adminUpdateDoctor(id, payload) {
  return api.put(`/api/admin/doctors/${id}`, payload).then(r => r.data)
}

export function adminToggleDoctor(id, active) {
  const action = active ? 'activate' : 'deactivate'
  return api.patch(`/api/doctors/${id}/${action}`).then(r => r.data)
}

export function adminGetClinics() {
  return api.get('/api/admin/clinics').then(r => r.data)
}

export function adminCreateClinic(payload) {
  return api.post('/api/admin/clinics', payload).then(r => r.data)
}

export function adminUpdateClinic(id, payload) {
  return api.put(`/api/admin/clinics/${id}`, payload).then(r => r.data)
}

export function adminGetMedicalServices() {
  return api.get('/api/admin/medical-services').then(r => r.data)
}

export function adminGetDoctorServices(doctorId) {
  return api.get(`/api/admin/doctors/${doctorId}/services`).then(r => r.data)
}

export function adminAddDoctorService(doctorId, payload) {
  return api.post(`/api/admin/doctors/${doctorId}/services`, payload).then(r => r.data)
}

export function adminUpdateDoctorService(id, payload) {
  return api.put(`/api/admin/doctor-services/${id}`, payload).then(r => r.data)
}

export function adminDeleteDoctorService(id) {
  return api.delete(`/api/admin/doctor-services/${id}`)
}

export function getSpecialties() {
  return api.get('/api/specialties').then(r => r.data)
}

export function getClinics() {
  return api.get('/api/clinics').then(r => r.data)
}
