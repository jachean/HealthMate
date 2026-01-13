// src/utils/date.js

export function isoToHHmm(isoString) {
  if (!isoString || typeof isoString !== 'string') return ''
  const tIndex = isoString.indexOf('T')
  if (tIndex === -1) return ''
  const timePart = isoString.slice(tIndex + 1) // HH:mm:ss+offset
  const parts = timePart.split(':')
  if (parts.length < 2) return ''
  return `${parts[0]}:${parts[1]}`
}

export function isoToDateKey(isoString) {
  if (!isoString || typeof isoString !== 'string') return ''
  return isoString.split('T')[0]
}

export function dateKeyToLabel(dateKey) {
  if (!dateKey) return ''
  const d = new Date(dateKey)
  return d.toLocaleDateString('en-GB', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  })
}

export function formatSlotRange(startAt, endAt) {
  const start = isoToHHmm(startAt)
  const end = isoToHHmm(endAt)
  if (!start || !end) return ''
  return `${start} - ${end}`
}
