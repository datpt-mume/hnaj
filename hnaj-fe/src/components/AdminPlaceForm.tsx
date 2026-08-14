import type { ChangeEvent } from 'react'
import type { AdminPlaceFormState } from '../utils/adminPlaceForm'
import { OPENING_DAYS } from '../utils/adminPlaceForm'
import type { FilterCategory, FilterDistrict } from '../services/metaService'
import type { AdminPlaceOpeningHour } from '../services/adminPlaceService'

type AdminPlaceTextField = Exclude<keyof AdminPlaceFormState, 'opening_hours'>

type AdminPlaceFormProps = {
  form: AdminPlaceFormState
  districts: FilterDistrict[]
  categories: FilterCategory[]
  onTextChange: (field: AdminPlaceTextField, value: string) => void
  onOpeningHoursChange: (hours: AdminPlaceOpeningHour[]) => void
}

function updateOpeningHour(
  hours: AdminPlaceOpeningHour[],
  index: number,
  update: (hour: AdminPlaceOpeningHour) => AdminPlaceOpeningHour,
): AdminPlaceOpeningHour[] {
  return hours.map((hour, currentIndex) => currentIndex === index ? update(hour) : hour)
}

export function AdminPlaceForm({ form, districts, categories, onTextChange, onOpeningHoursChange }: AdminPlaceFormProps) {
  function handleTextChange(field: AdminPlaceTextField) {
    return (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
      onTextChange(field, event.target.value)
    }
  }

  function handleOpeningHourChange(index: number, update: (hour: AdminPlaceOpeningHour) => AdminPlaceOpeningHour) {
    onOpeningHoursChange(updateOpeningHour(form.opening_hours, index, update))
  }

  return (
    <>
      <div className="admin-form__grid">
        <label>
          Tên địa điểm *
          <input value={form.name} onChange={handleTextChange('name')} required />
        </label>
        <label>
          Địa chỉ *
          <input value={form.address_text} onChange={handleTextChange('address_text')} required />
        </label>
        <label>
          Quận *
          <select value={form.district_id} onChange={handleTextChange('district_id')} required>
            <option value="">Chọn quận</option>
            {districts.map((district) => <option key={district.id} value={district.id}>{district.name}</option>)}
          </select>
        </label>
        <label>
          Danh mục *
          <select value={form.category_id} onChange={handleTextChange('category_id')} required>
            <option value="">Chọn danh mục</option>
            {categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
          </select>
        </label>
        <label>
          Google Maps URL *
          <input type="url" value={form.google_maps_url} onChange={handleTextChange('google_maps_url')} required />
        </label>
        <label>
          Google Place ID
          <input value={form.google_place_id} onChange={handleTextChange('google_place_id')} />
        </label>
        <label>
          Số điện thoại
          <input type="tel" value={form.phone} onChange={handleTextChange('phone')} />
        </label>
        <label>
          Website
          <input type="url" value={form.website_url} onChange={handleTextChange('website_url')} />
        </label>
        <label>
          Vĩ độ *
          <input type="number" step="any" value={form.latitude} onChange={handleTextChange('latitude')} required />
        </label>
        <label>
          Kinh độ *
          <input type="number" step="any" value={form.longitude} onChange={handleTextChange('longitude')} required />
        </label>
        <label>
          Giá thấp nhất
          <input type="number" min="0" step="1" value={form.min_price} onChange={handleTextChange('min_price')} />
        </label>
        <label>
          Giá cao nhất
          <input type="number" min="0" step="1" value={form.max_price} onChange={handleTextChange('max_price')} />
        </label>
        <label>
          Mô tả
          <textarea value={form.description} onChange={handleTextChange('description')} rows={3} />
        </label>
        <label>
          Trạng thái
          <select value={form.status} onChange={handleTextChange('status')}>
            <option value="active">active</option>
            <option value="hidden">hidden</option>
          </select>
        </label>
      </div>

      <fieldset className="admin-form__section">
        <legend>Giờ mở cửa</legend>
        {form.opening_hours.map((hour, index) => {
          const day = OPENING_DAYS[index]
          return (
            <div className="admin-hours-row" key={hour.day_of_week}>
              <span>{day?.label ?? hour.day_of_week}</span>
              <select
                aria-label={`Loại giờ ${day?.label ?? hour.day_of_week}`}
                value={hour.schedule_type}
                onChange={(event) => handleOpeningHourChange(index, (currentHour) => ({ ...currentHour, schedule_type: event.target.value }))}
              >
                <option value="regular">regular</option>
                <option value="all_day">all_day</option>
                <option value="closed">closed</option>
              </select>
              {hour.schedule_type === 'regular' ? (
                <>
                  <input
                    aria-label={`Giờ mở ${day?.label ?? hour.day_of_week}`}
                    type="time"
                    value={hour.opens_at ?? '08:00'}
                    onChange={(event) => handleOpeningHourChange(index, (currentHour) => ({ ...currentHour, opens_at: event.target.value }))}
                  />
                  <input
                    aria-label={`Giờ đóng ${day?.label ?? hour.day_of_week}`}
                    type="time"
                    value={hour.closes_at ?? '22:00'}
                    onChange={(event) => handleOpeningHourChange(index, (currentHour) => ({ ...currentHour, closes_at: event.target.value }))}
                  />
                </>
              ) : null}
            </div>
          )
        })}
      </fieldset>
    </>
  )
}
