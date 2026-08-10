import type { FormEvent } from 'react'
import { useState } from 'react'
import { FormField } from './FormField'
import { GoogleAuthButton } from './GoogleAuthButton'

type AuthLoginFormProps = {
  admin?: boolean
  submitLabel: string
  submittingLabel: string
  usernameLabel: string
  usernameHelper?: string
  error?: string
  onSubmit: (username: string, password: string) => Promise<void>
  onGoogleError?: (message: string) => void
}

export function AuthLoginForm({
  admin = false,
  submitLabel,
  submittingLabel,
  usernameLabel,
  usernameHelper,
  error = '',
  onSubmit,
  onGoogleError,
}: AuthLoginFormProps) {
  const [username, setUsername] = useState('')
  const [password, setPassword] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setIsSubmitting(true)

    try {
      await onSubmit(username, password)
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <form className="auth-form" onSubmit={handleSubmit} noValidate>
      <FormField
        id={admin ? 'admin-username' : 'username'}
        name="username"
        label={usernameLabel}
        autoComplete="username"
        value={username}
        onChange={(event) => setUsername(event.target.value)}
        helper={usernameHelper}
        required
      />
      <FormField
        id={admin ? 'admin-password' : 'password'}
        name="password"
        label="Mật khẩu"
        type="password"
        autoComplete="current-password"
        value={password}
        onChange={(event) => setPassword(event.target.value)}
        required
      />

      <div className="form-feedback" aria-live="polite">{error}</div>

      <button className="button button--primary" type="submit" disabled={isSubmitting}>
        {isSubmitting ? submittingLabel : submitLabel}
      </button>

      {!admin ? (
        <>
          <div className="auth-separator" aria-hidden="true">
            <span>hoặc</span>
          </div>
          <GoogleAuthButton onError={onGoogleError ?? (() => undefined)} />
        </>
      ) : null}
    </form>
  )
}
