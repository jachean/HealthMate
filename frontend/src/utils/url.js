const apiBase = import.meta.env.VITE_API_BASE_URL || ''

/**
 * Resolves a server-relative upload path (e.g. "/uploads/doctors/file.jpg")
 * to an absolute URL using the configured API base URL.
 * Returns null if path is falsy.
 */
export function uploadUrl(path) {
  if (!path) return null
  if (path.startsWith('http')) return path
  return apiBase + path
}
