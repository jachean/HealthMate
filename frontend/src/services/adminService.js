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

export function adminGetAppointment(id) {
  return api.get(`/api/admin/appointments/${id}`).then(r => r.data)
}

export function adminCreateAppointment(payload) {
  return api.post('/api/admin/appointments', payload).then(r => r.data)
}

export function adminGetAppointments({ page = 1, limit = 20, dateFrom, dateTo, doctorId, clinicId, status, patient } = {}) {
  return api.get('/api/admin/appointments', {
    params: { page, limit, dateFrom, dateTo, doctorId, clinicId, status, patient },
  }).then(r => r.data)
}

export function adminCancelAppointment(id) {
  return api.patch(`/api/admin/appointments/${id}/cancel`)
}

export function adminCreateMedicalService(payload) {
  return api.post('/api/admin/medical-services', payload).then(r => r.data)
}

export function adminDeleteMedicalService(id) {
  return api.delete(`/api/admin/medical-services/${id}`)
}

export function adminGetSpecialties() {
  return api.get('/api/admin/specialties').then(r => r.data)
}

export function adminCreateSpecialty(payload) {
  return api.post('/api/admin/specialties', payload).then(r => r.data)
}

export function adminDeleteSpecialty(id) {
  return api.delete(`/api/admin/specialties/${id}`)
}

export function adminRegenerateSlots() {
  return api.post('/api/admin/tools/regenerate-slots')
}

export function adminGetAnalytics(period = 'month') {
  return api.get('/api/admin/analytics', { params: { period } }).then(r => r.data)
}

export function adminGetUsers({ page = 1, limit = 20, search } = {}) {
  return api.get('/api/admin/users', { params: { page, limit, search } }).then(r => r.data)
}

export function adminDeactivateUser(id) {
  return api.patch(`/api/admin/users/${id}/deactivate`)
}

export function adminActivateUser(id) {
  return api.patch(`/api/admin/users/${id}/activate`)
}

export function adminMakeClinicAdmin(userId, clinicId) {
  return api.post(`/api/admin/users/${userId}/make-clinic-admin`, { clinicId })
}

export function adminRemoveClinicAdmin(userId) {
  return api.delete(`/api/admin/users/${userId}/remove-clinic-admin`)
}

export function adminGetUnlinkedDoctors() {
  return api.get('/api/admin/doctors/unlinked').then(r => r.data)
}

export function adminMakeDoctor(userId, doctorId) {
  return api.post(`/api/admin/users/${userId}/make-doctor`, { doctorId }).then(r => r.data)
}

export function adminRemoveDoctor(userId) {
  return api.delete(`/api/admin/users/${userId}/remove-doctor`).then(r => r.data)
}

export function adminGetDoctorUnavailability(doctorId) {
  return api.get(`/api/admin/doctors/${doctorId}/unavailability`).then(r => r.data)
}

export function adminAddDoctorUnavailability(doctorId, payload) {
  return api.post(`/api/admin/doctors/${doctorId}/unavailability`, payload).then(r => r.data)
}

export function adminDeleteDoctorUnavailability(doctorId, unavailId) {
  return api.delete(`/api/admin/doctors/${doctorId}/unavailability/${unavailId}`)
}

export function getSpecialties() {
  return api.get('/api/specialties').then(r => r.data)
}

export function getClinics() {
  return api.get('/api/clinics').then(r => r.data)
}

export function adminUploadFile(file, type) {
  const form = new FormData()
  form.append('file', file)
  return api.post(`/api/admin/upload?type=${type}`, form).then(r => r.data.path)
}
