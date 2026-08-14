import { useState } from 'react'
import type { FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { ApiRequestError, getApiErrorMessage } from '../services/httpClient'
import { submitManagerApplication } from '../services/userManagerApplicationService'

type ManagerApplicationFormProps = {
  placeId: number
  placeName: string
  isAuthenticated: boolean
}

export function ManagerApplicationForm({ placeId, placeName, isAuthenticated }: ManagerApplicationFormProps) {
  const [email, setEmail] = useState('')
  const [representativeName, setRepresentativeName] = useState('')
  const [proofReference, setProofReference] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [feedback, setFeedback] = useState<{ type: 'success' | 'error'; message: string } | null>(null)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSubmitting(true)
    setFeedback(null)
    try {
      await submitManagerApplication({
        place_id: placeId,
        email: email || undefined,
        representative_name: representativeName || undefined,
        proof_reference: proofReference || undefined,
      })
      setFeedback({ type: 'success', message: 'Đã gửi đơn. Admin sẽ xem xét và phản hồi qua email.' })
      setEmail('')
      setRepresentativeName('')
      setProofReference('')
    } catch (error) {
      const message = error instanceof ApiRequestError && error.status === 409
        ? 'Bạn đã có đơn đang chờ xử lý cho địa điểm này.'
        : getApiErrorMessage(error, 'Không thể gửi đơn lúc này. Vui lòng thử lại.')
      setFeedback({ type: 'error', message })
    } finally {
      setSubmitting(false)
    }
  }

  if (!isAuthenticated) {
    return (
      <section className="place-manager-application" aria-labelledby="manager-application-title">
        <h2 id="manager-application-title">Bạn là chủ địa điểm?</h2>
        <p>Đăng nhập để gửi yêu cầu quản lý “{placeName}”.</p>
        <Link className="button button--secondary button--link" to="/login">Đăng nhập để đăng ký</Link>
      </section>
    )
  }

  return (
    <section className="place-manager-application" aria-labelledby="manager-application-title">
      <h2 id="manager-application-title">Đăng ký quản lý địa điểm</h2>
      <p>Gửi thông tin để Admin xem xét quyền quản lý “{placeName}”.</p>
      <form className="place-manager-application__form" onSubmit={handleSubmit}>
        <label>
          Email nhận phản hồi
          <input type="email" autoComplete="email" value={email} onChange={(event) => setEmail(event.target.value)} />
        </label>
        <label>
          Tên người đại diện
          <input autoComplete="name" value={representativeName} onChange={(event) => setRepresentativeName(event.target.value)} />
        </label>
        <label>
          Thông tin chứng minh
          <textarea rows={3} value={proofReference} onChange={(event) => setProofReference(event.target.value)} />
        </label>
        {feedback ? (
          <p className={`admin-feedback admin-feedback--${feedback.type}`} role={feedback.type === 'error' ? 'alert' : 'status'}>
            {feedback.message}
          </p>
        ) : null}
        <button className="button button--primary" type="submit" disabled={submitting}>
          {submitting ? 'Đang gửi…' : 'Gửi đơn đăng ký'}
        </button>
      </form>
    </section>
  )
}
