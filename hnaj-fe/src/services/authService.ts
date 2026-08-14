import { apiRequest } from './httpClient'
import { adminTokenStorage, userTokenStorage } from './tokenStorage'

export type UserRole = 'user' | 'sub_admin' | 'admin'

export type AuthUser = {
  id: number
  username: string
  full_name: string
  email: string
  avatar_url: string | null
  status: 'active' | 'suspended' | 'disabled'
  email_verified: boolean
  roles: UserRole[]
}

export type AuthResult = {
  user: AuthUser
  token: string
}

export type RegisterPayload = {
  username: string
  full_name: string
  email: string
  password: string
  password_confirmation: string
}

export async function register(payload: RegisterPayload) {
  return apiRequest<{ user: AuthUser }>('/auth/register', {
    method: 'POST',
    body: payload,
  })
}

export async function login(username: string, password: string) {
  return apiRequest<AuthResult>('/auth/login', {
    method: 'POST',
    body: { username, password },
  })
}

export async function loginAdmin(username: string, password: string) {
  return apiRequest<AuthResult>('/admin/auth/login', {
    method: 'POST',
    body: { username, password },
  })
}

export async function getCurrentUser() {
  return apiRequest<{ user: AuthUser }>('/auth/me', {
    token: userTokenStorage.get(),
  })
}

export async function getCurrentAdmin() {
  return apiRequest<{ user: AuthUser }>('/admin/auth/me', {
    token: adminTokenStorage.get(),
  })
}

export async function logout() {
  return apiRequest<null>('/auth/logout', {
    method: 'POST',
    token: userTokenStorage.get(),
  })
}

export async function logoutAdmin() {
  return apiRequest<null>('/admin/auth/logout', {
    method: 'POST',
    token: adminTokenStorage.get(),
  })
}

export async function verifyEmail(token: string) {
  return apiRequest<{ user: AuthUser }>('/auth/email/verify', {
    method: 'POST',
    body: { token },
  })
}

export async function completeAccountSetup(token: string, password: string, passwordConfirmation: string) {
  return apiRequest<{ user: AuthUser }>('/auth/account/setup', {
    method: 'POST',
    body: { token, password, password_confirmation: passwordConfirmation },
  })
}

export async function resendVerification(email: string) {
  return apiRequest<null>('/auth/email/resend', {
    method: 'POST',
    body: { email },
  })
}

export async function getGoogleAuthorizationUrl() {
  return apiRequest<{ authorization_url: string }>('/auth/google/redirect', {
    credentials: 'include',
  })
}

export async function exchangeGoogleCode(code: string) {
  return apiRequest<AuthResult>('/auth/google/exchange', {
    method: 'POST',
    body: { code },
    credentials: 'include',
  })
}
