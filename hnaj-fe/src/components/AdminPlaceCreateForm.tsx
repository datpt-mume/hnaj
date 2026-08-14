import { useState } from 'react'
import type { FormEvent } from 'react'
import { AdminPlaceForm } from './AdminPlaceForm'
import type { FilterCategory, FilterDistrict } from '../services/metaService'
import { EMPTY_ADMIN_PLACE_FORM, toCreatePlacePayload, type AdminPlaceFormState } from '../utils/adminPlaceForm'
import { createAdminPlace } from '../services/adminPlaceService'
import { getApiErrorMessage } from '../services/httpClient'

type AdminPlaceCreateFormProps = {
  districts: FilterDistrict[]
  categories: FilterCategory[]
  onCreated: () => Promise<void>
  onClose: () => void
}

function isCreateFormValid(form: AdminPlaceFormState): boolean {
  return Boolean(
    form.name.trim()
    && form.address_text.trim()
    && form.district_id
    && form.category_id
    && form.google_maps_url.trim()
    && form.latitude.trim()
    && form.longitude.trim(),
  )
}

export function AdminPlaceCreateForm({ districts, categories, onCreated, onClose }: AdminPlaceCreateFormProps) {
  const [form, setForm] = useState<AdminPlaceFormState>(EMPTY_ADMIN_PLACE_FORM)
  const [creating, setCreating] = useState(false)
  const [error, setError] = useState<string | null>(null)

  function updateTextField(field: Exclude<keyof AdminPlaceFormState, 'opening_hours'>, value: string) {
    setForm((currentForm) => ({ ...currentForm, [field]: value }))
  }

  function updateOpeningHours(opening_hours: AdminPlaceFormState['opening_hours']) {
    setForm((currentForm) => ({ ...currentForm, opening_hours }))
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError(null)

    if (!isCreateFormValid(form)) {
      setError('Vui lòng điền đầy đủ các trường bắt buộc.')
      return
    }

    setCreating(true)
    try {
      await createAdminPlace(toCreatePlacePayload(form))
      await onCreated()
      onClose()
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, 'Không tạo được địa điểm.'))
    } finally {
      setCreating(false)
    }
  }

  return (
    <form className="admin-form" onSubmit={handleSubmit} noValidate>
      <AdminPlaceForm
        form={form}
        districts={districts}
        categories={categories}
        onTextChange={updateTextField}
        onOpeningHoursChange={updateOpeningHours}
      />
      {error ? <p className="admin-feedback admin-feedback--error" role="alert">{error}</p> : null}
      <button className="button button--primary" type="submit" disabled={creating}>
        {creating ? 'Đang tạo…' : 'Tạo Place'}
      </button>
    </form>
  )
}
