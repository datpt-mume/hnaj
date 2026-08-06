import { useId } from 'react'

type PriceRangeSliderProps = {
  min: number
  max: number
  step?: number
  onChange: (min: number, max: number) => void
}

function formatVnd(value: number): string {
  return `${new Intl.NumberFormat('vi-VN').format(value)}đ`
}

export function PriceRangeSlider({
  min,
  max,
  step = 10000,
  onChange,
}: PriceRangeSliderProps) {
  const minId = useId()
  const maxId = useId()

  return (
    <div className="price-range">
      <div className="price-range__output" aria-live="polite">
        <span>{formatVnd(min)}</span>
        <span aria-hidden="true">–</span>
        <span>{formatVnd(max)}</span>
      </div>
      <div className="price-range__inputs">
        <label className="price-range__field" htmlFor={minId}>
          <span className="sr-only">Giá tối thiểu</span>
          <input
            id={minId}
            type="number"
            min={0}
            max={max}
            step={step}
            value={min}
            onChange={(event) => {
              const next = Number(event.target.value)
              if (!Number.isNaN(next)) onChange(next, max)
            }}
          />
        </label>
        <label className="price-range__field" htmlFor={maxId}>
          <span className="sr-only">Giá tối đa</span>
          <input
            id={maxId}
            type="number"
            min={min}
            step={step}
            value={max}
            onChange={(event) => {
              const next = Number(event.target.value)
              if (!Number.isNaN(next)) onChange(min, next)
            }}
          />
        </label>
      </div>
      <div className="price-range__slider">
        <input
          type="range"
          min={0}
          max={500000}
          step={step}
          value={min}
          aria-label="Giá tối thiểu"
          onChange={(event) => onChange(Number(event.target.value), max)}
        />
        <input
          type="range"
          min={0}
          max={500000}
          step={step}
          value={max}
          aria-label="Giá tối đa"
          onChange={(event) => onChange(min, Number(event.target.value))}
        />
      </div>
    </div>
  )
}