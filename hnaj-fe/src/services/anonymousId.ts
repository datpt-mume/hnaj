const anonymousIdKey = 'hnaj.anonymous_id'

function randomId(): string {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID()
  }

  return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2)}`
}

/**
 * Định danh tạm thời cho khách chưa đăng nhập, dùng cho anonymous visit.
 * UUID lưu localStorage; xóa/đổi định danh được xem như khách mới.
 */
export function getAnonymousId(): string {
  try {
    const existing = window.localStorage.getItem(anonymousIdKey)
    if (existing) return existing

    const next = randomId()
    window.localStorage.setItem(anonymousIdKey, next)
    return next
  } catch {
    return randomId()
  }
}