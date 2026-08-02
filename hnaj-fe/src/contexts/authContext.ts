import { createContext } from 'react'
import type { AuthResult, AuthUser } from '../services/authService'

export type AuthContextValue = {
  user: AuthUser | null
  adminUser: AuthUser | null
  isLoading: boolean
  isAdminLoading: boolean
  signIn: (username: string, password: string) => Promise<AuthUser>
  signInAdmin: (username: string, password: string) => Promise<AuthUser>
  signOut: () => Promise<void>
  signOutAdmin: () => Promise<void>
  acceptUserAuth: (result: AuthResult) => void
}

export const AuthContext = createContext<AuthContextValue | null>(null)
