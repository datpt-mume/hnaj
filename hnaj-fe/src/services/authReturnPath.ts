const AUTH_RETURN_KEY = 'hnaj:auth:returnTo'

function isSafeInternalPath(path: string): boolean {
  return path.startsWith('/') && !path.startsWith('//') && !path.startsWith('/\\')
}

export function getAuthReturnPath(): string | null {
  const stored = sessionStorage.getItem(AUTH_RETURN_KEY)
  if (stored && isSafeInternalPath(stored)) return stored
  return null
}

export function setAuthReturnPath(path: string) {
  if (isSafeInternalPath(path)) {
    sessionStorage.setItem(AUTH_RETURN_KEY, path)
  }
}

export function clearAuthReturnPath() {
  sessionStorage.removeItem(AUTH_RETURN_KEY)
}