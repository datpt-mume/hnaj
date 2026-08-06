import { RiMapPin2Line, RiMotorbikeLine } from 'react-icons/ri'
import { CATEGORIES, DISTRICTS, TAGS } from '../services/metaService'
import { FilterChip } from './FilterChip'
import { PriceRangeSlider } from './PriceRangeSlider'
import { Toggle } from './Toggle'

export type FilterState = {
  categoryId: number | null
  districtId: number | null
  minPrice: number
  maxPrice: number
  tagIds: number[]
  openNow: boolean
  useLocation: boolean
  locationDenied: boolean
}

const DEFAULT_MIN_PRICE = 0
const DEFAULT_MAX_PRICE = 500000

type FilterPanelProps = {
  filters: FilterState
  onChange: (next: FilterState) => void
}

export function FilterPanel({ filters, onChange }: FilterPanelProps) {
  function patch(p: Partial<FilterState>) {
    onChange({ ...filters, ...p })
  }

  function toggleTag(id: number) {
    const tagIds = filters.tagIds.includes(id)
      ? filters.tagIds.filter((t) => t !== id)
      : [...filters.tagIds, id]
    patch({ tagIds })
  }

  async function requestLocation() {
    if (!('geolocation' in navigator)) {
      patch({ useLocation: false, locationDenied: true })
      return
    }

    try {
      const position = await new Promise<GeolocationPosition>((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
          enableHighAccuracy: false,
          timeout: 8000,
        })
      })
      patch({
        useLocation: true,
        locationDenied: false,
      })
      // Lưu tọa độ để HomePage dùng khi gọi random.
      window.dispatchEvent(
        new CustomEvent('hnaj:location', {
          detail: {
            lat: position.coords.latitude,
            lng: position.coords.longitude,
          },
        }),
      )
    } catch {
      patch({ useLocation: false, locationDenied: true })
    }
  }

  return (
    <section className="filter-panel" aria-label="Bộ lọc khám phá">
      <fieldset className="filter-group">
        <legend>Danh mục</legend>
        <div className="chip-row">
          <FilterChip
            id="category-none"
            label="Tất cả"
            selected={filters.categoryId === null}
            onToggle={() => patch({ categoryId: null })}
          />
          {CATEGORIES.map((category) => (
            <FilterChip
              key={category.id}
              id={`category-${category.id}`}
              label={category.name}
              selected={filters.categoryId === category.id}
              onToggle={() => patch({ categoryId: category.id })}
            />
          ))}
        </div>
      </fieldset>

      <fieldset className="filter-group">
        <legend>Quận / huyện</legend>
        <label className="filter-select">
          <span className="sr-only">Chọn quận huyện</span>
          <select
            value={filters.districtId ?? ''}
            onChange={(event) =>
              patch({
                districtId: event.target.value ? Number(event.target.value) : null,
              })
            }
          >
            <option value="">Toàn Hà Nội</option>
            {DISTRICTS.map((district) => (
              <option key={district.id} value={district.id}>
                {district.name}
              </option>
            ))}
          </select>
        </label>
      </fieldset>

      <fieldset className="filter-group">
        <legend>Khoảng giá</legend>
        <PriceRangeSlider
          min={filters.minPrice}
          max={filters.maxPrice}
          onChange={(min, max) => patch({ minPrice: min, maxPrice: max })}
        />
      </fieldset>

      <fieldset className="filter-group">
        <legend>Tags</legend>
        <p className="filter-hint">
          Chọn nhiều tag đồng nghĩa với việc địa điểm phải có đủ tất cả các tag đó.
        </p>
        <div className="chip-row">
          {TAGS.map((tag) => (
            <FilterChip
              key={tag.id}
              id={`tag-${tag.id}`}
              label={tag.name}
              selected={filters.tagIds.includes(tag.id)}
              onToggle={() => toggleTag(tag.id)}
            />
          ))}
        </div>
      </fieldset>

      <div className="filter-group filter-group--compact">
        <Toggle
          id="open-now"
          label="Đang mở cửa"
          hint="Bỏ chọn để xem cả nơi chưa rõ giờ"
          checked={filters.openNow}
          onChange={(openNow) => patch({ openNow })}
        />
        <button
          className={`filter-location${filters.useLocation ? ' filter-location--active' : ''}`}
          type="button"
          onClick={() => void requestLocation()}
          aria-pressed={filters.useLocation}
        >
          <RiMotorbikeLine aria-hidden="true" />
          {filters.useLocation ? 'Đang lọc theo vị trí' : 'Dùng vị trí của tôi'}
        </button>
        {filters.locationDenied ? (
          <p className="filter-hint" role="status">
            <RiMapPin2Line aria-hidden="true" />
            Không có vị trí — đang lọc theo quận đã chọn.
          </p>
        ) : null}
      </div>
    </section>
  )
}

export { DEFAULT_MAX_PRICE, DEFAULT_MIN_PRICE }