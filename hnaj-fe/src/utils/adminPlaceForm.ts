import type {
  AdminPlace,
  AdminPlaceOpeningHour,
  CreateAdminPlacePayload,
  UpdateAdminPlacePayload,
} from '../services/adminPlaceService'

export const OPENING_DAYS = [
  { label: 'T2', dayOfWeek: 2 },
  { label: 'T3', dayOfWeek: 3 },
  { label: 'T4', dayOfWeek: 4 },
  { label: 'T5', dayOfWeek: 5 },
  { label: 'T6', dayOfWeek: 6 },
  { label: 'T7', dayOfWeek: 7 },
  { label: 'CN', dayOfWeek: 8 },
] as const

export type AdminPlaceFormState = {
  name: string
  address_text: string
  district_id: string
  category_id: string
  phone: string
  website_url: string
  google_maps_url: string
  google_place_id: string
  latitude: string
  longitude: string
  min_price: string
  max_price: string
  description: string
  status: string
  opening_hours: AdminPlaceOpeningHour[]
}

export const EMPTY_ADMIN_PLACE_FORM: AdminPlaceFormState = {
  name: '',
  address_text: '',
  district_id: '',
  category_id: '',
  phone: '',
  website_url: '',
  google_maps_url: '',
  google_place_id: '',
  latitude: '',
  longitude: '',
  min_price: '',
  max_price: '',
  description: '',
  status: 'active',
  opening_hours: [],
}

export function toOpeningHoursState(hours: AdminPlaceOpeningHour[]): AdminPlaceOpeningHour[] {
  const hoursByDay = new Map(hours.map((hour) => [hour.day_of_week, hour]))

  return OPENING_DAYS.map(({ dayOfWeek }) =>
    hoursByDay.get(dayOfWeek) ?? {
      day_of_week: dayOfWeek,
      schedule_type: 'regular',
      opens_at: '08:00',
      closes_at: '22:00',
    },
  )
}

export function formStateFromPlace(place: AdminPlace): AdminPlaceFormState {
  return {
    name: place.name,
    address_text: place.address_text,
    district_id: String(place.district_id),
    category_id: String(place.category_id),
    phone: place.phone ?? '',
    website_url: place.website_url ?? '',
    google_maps_url: place.google_maps_url,
    google_place_id: place.google_place_id ?? '',
    latitude: String(place.latitude),
    longitude: String(place.longitude),
    min_price: place.min_price === null ? '' : String(place.min_price),
    max_price: place.max_price === null ? '' : String(place.max_price),
    description: place.description ?? '',
    status: place.status,
    opening_hours: toOpeningHoursState(place.opening_hours),
  }
}

function nullableNumber(value: string): number | null {
  return value.trim() === '' ? null : Number(value)
}

export function toCreatePlacePayload(form: AdminPlaceFormState): CreateAdminPlacePayload {
  return {
    name: form.name.trim(),
    address_text: form.address_text.trim(),
    district_id: Number(form.district_id),
    category_id: Number(form.category_id),
    tag_ids: [],
    phone: form.phone.trim() || null,
    website_url: form.website_url.trim() || null,
    google_maps_url: form.google_maps_url.trim(),
    google_place_id: form.google_place_id.trim() || null,
    latitude: Number(form.latitude),
    longitude: Number(form.longitude),
    min_price: nullableNumber(form.min_price),
    max_price: nullableNumber(form.max_price),
    description: form.description.trim() || null,
    status: form.status,
    opening_hours: form.opening_hours,
    images: [],
    thumbnail_image_id: null,
  }
}

export function toUpdatePlacePayload(form: AdminPlaceFormState): UpdateAdminPlacePayload {
  return {
    ...toCreatePlacePayload(form),
    deleted_image_ids: [],
  }
}
