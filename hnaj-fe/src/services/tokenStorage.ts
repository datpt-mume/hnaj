const userTokenKey = 'hnaj.auth.user_token'
const adminTokenKey = 'hnaj.auth.admin_token'

function readToken(key: string): string | null {
  return window.localStorage.getItem(key)
}

function writeToken(key: string, token: string): void {
  window.localStorage.setItem(key, token)
}

function removeToken(key: string): void {
  window.localStorage.removeItem(key)
}

export const userTokenStorage = {
  get: () => readToken(userTokenKey),
  set: (token: string) => writeToken(userTokenKey, token),
  clear: () => removeToken(userTokenKey),
}

export const adminTokenStorage = {
  get: () => readToken(adminTokenKey),
  set: (token: string) => writeToken(adminTokenKey, token),
  clear: () => removeToken(adminTokenKey),
}
