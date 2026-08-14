import { useCallback, useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import {
  getCurrentAdmin,
  getCurrentUser,
  login,
  loginAdmin,
  logout,
  logoutAdmin,
  updateProfile,
} from '../services/authService'
import type { AuthResult, AuthUser } from '../services/authService'
import { adminTokenStorage, userTokenStorage } from '../services/tokenStorage'
import { AuthContext } from './authContext'

function restoreSession(
  tokenStorage: { get: () => string | null; clear: () => void },
  loadCurrentUser: () => Promise<{ data: { user: AuthUser } }>,
  setUser: (user: AuthUser) => void,
  setLoading: (loading: boolean) => void,
): () => void {
  let isActive = true

  async function restore(): Promise<void> {
    if (!tokenStorage.get()) {
      setLoading(false)
      return
    }

    try {
      const response = await loadCurrentUser()
      if (isActive) setUser(response.data.user)
    } catch {
      tokenStorage.clear()
    } finally {
      if (isActive) setLoading(false)
    }
  }

  void restore()

  return () => {
    isActive = false
  }
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null)
  const [adminUser, setAdminUser] = useState<AuthUser | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [isAdminLoading, setIsAdminLoading] = useState(true)

  useEffect(
    () => restoreSession(userTokenStorage, getCurrentUser, setUser, setIsLoading),
    [],
  )

  useEffect(
    () => restoreSession(adminTokenStorage, getCurrentAdmin, setAdminUser, setIsAdminLoading),
    [],
  )

  const acceptUserAuth = useCallback((result: AuthResult) => {
    userTokenStorage.set(result.token)
    setUser(result.user)
  }, [])

  const signIn = useCallback(
    async (username: string, password: string) => {
      const response = await login(username, password)
      acceptUserAuth(response.data)
      return response.data.user
    },
    [acceptUserAuth],
  )

  const signInAdmin = useCallback(async (username: string, password: string) => {
    const response = await loginAdmin(username, password)
    adminTokenStorage.set(response.data.token)
    setAdminUser(response.data.user)
    return response.data.user
  }, [])

  const signOut = useCallback(async () => {
    try {
      await logout()
    } finally {
      userTokenStorage.clear()
      setUser(null)
    }
  }, [])

  const signOutAdmin = useCallback(async () => {
    try {
      await logoutAdmin()
    } finally {
      adminTokenStorage.clear()
      setAdminUser(null)
    }
  }, [])

  const updateUserProfile = useCallback(async (fullName: string) => {
    const response = await updateProfile(fullName)
    setUser(response.data.user)
    return response.data.user
  }, [])

  const value = useMemo(
    () => ({
      user,
      adminUser,
      isLoading,
      isAdminLoading,
      signIn,
      signInAdmin,
      signOut,
      signOutAdmin,
      acceptUserAuth,
      updateProfile: updateUserProfile,
    }),
    [
      user,
      adminUser,
      isLoading,
      isAdminLoading,
      signIn,
      signInAdmin,
      signOut,
      signOutAdmin,
      acceptUserAuth,
      updateUserProfile,
    ],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
