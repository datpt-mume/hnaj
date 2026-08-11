import { useState } from 'react'
import type { Dispatch, SetStateAction } from 'react'
import { RiArrowLeftSLine, RiArrowRightSLine, RiMapPin2Line, RiMotorbikeLine } from 'react-icons/ri'
import type { FilterCategory, FilterDistrict, FilterTag } from '../services/metaService'
import { FilterChip } from './FilterChip'
import { FormDropdown, type FormDropdownOption } from './FormDropdown'
import { PriceRangeSlider } from './PriceRangeSlider'

export type LocationCoordinates = {
  lat: number
  lng: number
}

export type FilterState = {
  categoryId: number | null
  districtId: number | null
  minPrice: number
  maxPrice: number
  tagIds: number[]
  openNow: boolean
  useLocation: boolean
  location: LocationCoordinates | null
  locationDenied: boolean
}

const DEFAULT_MIN_PRICE = 0
const DEFAULT_MAX_PRICE = 500000
const TAGS_PER_PAGE = 6

type FilterPanelProps = {
  filters: FilterState
  onChange: Dispatch<SetStateAction<FilterState>>
  categories: FilterCategory[]
  districts: FilterDistrict[]
  tags: FilterTag[]
  disabled?: boolean
}

export function FilterPanel({ filters, onChange, categories, districts, tags, disabled = false }: FilterPanelProps) {
  const districtOptions: FormDropdownOption<number | null>[] = [
    { value: null, label: 'Toàn Hà Nội' },
    ...districts.map((district) => ({ value: district.id, label: district.name })),
  ]
  const categoryOptions: FormDropdownOption<number | null>[] = [
    { value: null, label: 'Tất cả danh mục' },
    ...categories.map((category) => ({ value: category.id, label: category.name })),
  ]
  const [isLocationLoading, setIsLocationLoading] = useState(false)
  const [tagPage, setTagPage] = useState(0)
  const [tagDirection, setTagDirection] = useState<'previous' | 'next'>('next')
  const tagPageCount = Math.max(1, Math.ceil(tags.length / TAGS_PER_PAGE))
  const visibleTags = tags.slice(tagPage * TAGS_PER_PAGE, (tagPage + 1) * TAGS_PER_PAGE)

  function patch(p: Partial<FilterState>) {
    onChange((current) => ({ ...current, ...p }))
  }

  function toggleTag(id: number) {
    const tagIds = filters.tagIds.includes(id)
      ? filters.tagIds.filter((t) => t !== id)
      : [...filters.tagIds, id]
    patch({ tagIds })
  }

  async function requestLocation() {
    if (isLocationLoading) return

    if (!('geolocation' in navigator)) {
      patch({ useLocation: false, location: null, locationDenied: true })
      return
    }

    setIsLocationLoading(true)
    patch({ locationDenied: false })

    try {
      const position = await new Promise<GeolocationPosition>((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
          enableHighAccuracy: false,
          timeout: 8000,
        })
      })
      patch({
        districtId: null,
        useLocation: true,
        location: {
          lat: position.coords.latitude,
          lng: position.coords.longitude,
        },
        locationDenied: false,
      })
    } catch {
      patch({ useLocation: false, location: null, locationDenied: true })
    } finally {
      setIsLocationLoading(false)
    }
  }

  function clearLocation() {
    patch({ useLocation: false, location: null, locationDenied: false })
  }

  function changeTagPage(direction: -1 | 1) {
    setTagDirection(direction > 0 ? 'next' : 'previous')
    setTagPage((current) => Math.max(0, Math.min(current + direction, tagPageCount - 1)))
  }

  return (
    <section className="filter-panel" aria-label="Bộ lọc khám phá">
      <fieldset className="filter-group filter-group--category filter-surface">
        <legend>Danh mục</legend>
        <FormDropdown
          value={filters.categoryId}
          options={categoryOptions}
          label="Chọn danh mục"
          disabled={disabled}
          onChange={(categoryId) => patch({ categoryId })}
        />
      </fieldset>

      <fieldset className="filter-group filter-group--district filter-surface">
        <legend>Chọn khu vực</legend>
        <div
          className={
            filters.useLocation && filters.location
              ? 'filter-location-choice filter-location-choice--located'
              : 'filter-location-choice'
          }
        >
          <FormDropdown
            value={filters.districtId}
            options={districtOptions}
            label="Chọn quận / huyện"
            disabled={disabled || filters.useLocation || isLocationLoading}
            onChange={(districtId) =>
              patch({
                districtId,
                useLocation: false,
                location: null,
                locationDenied: false,
              })
            }
          />
          <span className="filter-location-choice__or" aria-hidden="true">hoặc</span>
          {filters.useLocation && filters.location ? (
            <div className="filter-location__selected" role="status">
              <div className="filter-location__details">
                <RiMapPin2Line aria-hidden="true" />
                <span>
                  <strong>Vị trí hiện tại</strong>
                  <small>Đang được sử dụng</small>
                </span>
              </div>
              <button className="filter-location__clear" type="button" onClick={clearLocation}>
                Bỏ vị trí
              </button>
            </div>
          ) : (
            <button
              className="filter-location"
              type="button"
              onClick={() => void requestLocation()}
              disabled={disabled || isLocationLoading}
              aria-busy={isLocationLoading}
            >
              <RiMotorbikeLine aria-hidden="true" />
              {isLocationLoading ? 'Đang lấy vị trí…' : 'Dùng vị trí của tôi'}
            </button>
          )}
        </div>
        {filters.locationDenied ? (
          <p className="filter-hint filter-location__error" role="status">
            <RiMapPin2Line aria-hidden="true" />
            Không lấy được vị trí. Bạn có thể chọn quận/huyện hoặc thử lại.
          </p>
        ) : null}
      </fieldset>

      <fieldset className="filter-group filter-group--price filter-surface">
        <legend className="sr-only">Khoảng giá</legend>
        <PriceRangeSlider
          min={filters.minPrice}
          max={filters.maxPrice}
          onChange={(min, max) => patch({ minPrice: min, maxPrice: max })}
        />
      </fieldset>

      <fieldset className="filter-group filter-group--tags filter-surface">
        <legend>Sở thích</legend>
        <div className="filter-group__meta">
          <p className="filter-hint">
            Chọn nhiều sở thích để kết quả khớp đủ tất cả lựa chọn của bạn.
          </p>
          <div className="chip-slider__controls" aria-label="Điều hướng nhóm sở thích">
            <button
              className="chip-slider__button"
              type="button"
              onClick={() => changeTagPage(-1)}
              disabled={disabled || tagPage === 0}
              aria-label="Xem nhóm sở thích trước"
            >
              <RiArrowLeftSLine aria-hidden="true" />
            </button>
            <span className="chip-slider__status" aria-live="polite">
              {tagPage + 1} / {tagPageCount}
            </span>
            <button
              className="chip-slider__button"
              type="button"
              onClick={() => changeTagPage(1)}
              disabled={disabled || tagPage === tagPageCount - 1}
              aria-label="Xem nhóm sở thích tiếp"
            >
              <RiArrowRightSLine aria-hidden="true" />
            </button>
          </div>
        </div>
        <div className="chip-slider" aria-label="Nhóm sở thích">
          <div className={`chip-row chip-row--paged chip-row--${tagDirection}`} key={tagPage} aria-live="polite">
            {visibleTags.map((tag) => (
              <FilterChip
                key={tag.id}
                id={`tag-${tag.id}`}
                label={tag.name}
                selected={filters.tagIds.includes(tag.id)}
                onToggle={() => toggleTag(tag.id)}
              />
            ))}
          </div>
        </div>
      </fieldset>
    </section>
  )
}

export { DEFAULT_MAX_PRICE, DEFAULT_MIN_PRICE }
