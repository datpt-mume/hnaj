import type { InputHTMLAttributes } from 'react'

type FormFieldProps = InputHTMLAttributes<HTMLInputElement> & {
  label: string
  error?: string
  helper?: string
}

export function FormField({
  label,
  error,
  helper,
  id,
  required,
  ...inputProps
}: FormFieldProps) {
  const messageId = `${id}-message`

  return (
    <div className={`form-field${error ? ' form-field--error' : ''}`}>
      <label htmlFor={id}>{label}</label>
      <input
        {...inputProps}
        id={id}
        required={required}
        aria-required={required || undefined}
        aria-invalid={error ? true : undefined}
        aria-describedby={messageId}
      />
      <p id={messageId} className="form-field__message">
        {error ?? helper ?? '\u00a0'}
      </p>
    </div>
  )
}
