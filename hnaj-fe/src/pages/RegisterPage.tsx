import { useState } from 'react'
import type { FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { AuthShell } from '../components/AuthShell'
import { FormField } from '../components/FormField'
import { register } from '../services/authService'
import { ApiRequestError } from '../services/httpClient'

type FieldErrors = Record<string, string>

export function RegisterPage() {
  const [form, setForm] = useState({
    username: '',
    full_name: '',
    email: '',
    password: '',
    password_confirmation: '',
  })
  const [errors, setErrors] = useState<FieldErrors>({})
  const [message, setMessage] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [registeredEmail, setRegisteredEmail] = useState('')

  function updateField(field: keyof typeof form, value: string) {
    setForm((current) => ({ ...current, [field]: value }))
    setErrors((current) => ({ ...current, [field]: '' }))
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setErrors({})
    setMessage('')
    setIsSubmitting(true)

    try {
      await register(form)
      setRegisteredEmail(form.email)
    } catch (requestError) {
      if (requestError instanceof ApiRequestError) {
        const nextErrors = Object.fromEntries(
          Object.entries(requestError.errors ?? {}).map(([field, values]) => [
            field,
            values[0] ?? '',
          ]),
        )
        setErrors(nextErrors)
        setMessage(requestError.message)
      } else {
        setMessage('Không thể tạo tài khoản lúc này. Hãy thử lại.')
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  if (registeredEmail) {
    return (
      <AuthShell
        title="Kiểm tra email"
        description="Một bước nữa là tài khoản sẵn sàng."
      >
        <div className="auth-success" role="status">
          <span className="auth-success__mark" aria-hidden="true">✓</span>
          <h3>Liên kết xác thực đã được gửi</h3>
          <p>
            Mở email <strong>{registeredEmail}</strong> và bấm liên kết xác thực trong vòng 24 giờ.
          </p>
        </div>
        <Link className="button button--primary button--link" to="/login">
          Về trang đăng nhập
        </Link>
      </AuthShell>
    )
  }

  return (
    <AuthShell
      title="Tạo tài khoản"
      description="Đăng ký tài khoản người dùng HNAJ."
    >
      <form className="auth-form auth-form--register" onSubmit={handleSubmit} noValidate>
        <FormField
          id="full-name"
          name="full_name"
          label="Họ và tên"
          autoComplete="name"
          value={form.full_name}
          onChange={(event) => updateField('full_name', event.target.value)}
          error={errors.full_name}
          required
        />
        <FormField
          id="register-username"
          name="username"
          label="Tên tài khoản"
          autoComplete="username"
          value={form.username}
          onChange={(event) => updateField('username', event.target.value)}
          error={errors.username}
          helper="3-50 ký tự: chữ thường, số, dấu chấm hoặc gạch dưới."
          required
        />
        <FormField
          id="email"
          name="email"
          label="Địa chỉ email"
          type="email"
          autoComplete="email"
          value={form.email}
          onChange={(event) => updateField('email', event.target.value)}
          error={errors.email}
          required
        />
        <div className="form-grid">
          <FormField
            id="register-password"
            name="password"
            label="Mật khẩu"
            type="password"
            autoComplete="new-password"
            value={form.password}
            onChange={(event) => updateField('password', event.target.value)}
            error={errors.password}
            helper="Tối thiểu 8 ký tự, có chữ và số."
            required
          />
          <FormField
            id="password-confirmation"
            name="password_confirmation"
            label="Nhập lại mật khẩu"
            type="password"
            autoComplete="new-password"
            value={form.password_confirmation}
            onChange={(event) => updateField('password_confirmation', event.target.value)}
            required
          />
        </div>

        <div className="form-feedback" aria-live="polite">
          {message}
        </div>

        <button className="button button--primary" type="submit" disabled={isSubmitting}>
          {isSubmitting ? 'Đang tạo tài khoản…' : 'Tạo tài khoản'}
        </button>
      </form>

      <p className="auth-switch">
        Đã có tài khoản? <Link to="/login">Đăng nhập</Link>
      </p>
    </AuthShell>
  )
}
