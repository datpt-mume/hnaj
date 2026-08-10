import { useEffect, useState } from 'react'
import type { FocusEvent } from 'react'

type FoodPosterSlide = {
  src: string
  alt: string
  caption: string
  detail: string
}

const slides: FoodPosterSlide[] = [
  { src: '/food-poster-slide-1.png', alt: 'Poster món ăn Việt Nam với tô bún bò Huế', caption: 'Huế / món đậm vị', detail: 'Một chút cay, một chút thương.' },
  { src: '/food-poster-slide-2.png', alt: 'Poster Hà Nội với phở và các địa danh thành phố', caption: 'Hà Nội / phở nóng', detail: 'Đi một vòng rồi ghé ăn.' },
  { src: '/food-poster-slide-3.png', alt: 'Poster ẩm thực Nam Định với nhiều món ăn địa phương', caption: 'Nam Định / vị quê nhà', detail: 'Món ngon kể chuyện nơi chốn.' },
]

export function FoodPosterSlideshow() {
  const [activeIndex, setActiveIndex] = useState(0)
  const [isHoverPaused, setIsHoverPaused] = useState(false)
  const [isFocusPaused, setIsFocusPaused] = useState(false)
  const isPaused = isHoverPaused || isFocusPaused
  const activeSlide = slides[activeIndex]

  useEffect(() => {
    if (isPaused) return undefined

    const intervalId = window.setInterval(() => {
      setActiveIndex((currentIndex) => (currentIndex + 1) % slides.length)
    }, 5000)

    return () => window.clearInterval(intervalId)
  }, [activeIndex, isPaused])

  function goTo(index: number) {
    setActiveIndex(index)
  }

  function handleViewportBlur(event: FocusEvent<HTMLDivElement>) {
    const nextFocusedElement = event.relatedTarget

    if (nextFocusedElement instanceof Node && event.currentTarget.contains(nextFocusedElement)) {
      return
    }

    setIsFocusPaused(false)
  }

  return (
    <section className="food-poster-slideshow" aria-label="Poster ẩm thực nổi bật">
      <div
        className="food-poster-slideshow__viewport"
        tabIndex={0}
        onFocus={() => setIsFocusPaused(true)}
        onBlur={handleViewportBlur}
        onMouseEnter={() => setIsHoverPaused(true)}
        onMouseLeave={() => setIsHoverPaused(false)}
      >
        <div className="food-poster-slideshow__frame">
          <img
            className="food-poster-slideshow__image"
            src={activeSlide.src}
            alt={activeSlide.alt}
            onError={(event) => {
              event.currentTarget.style.opacity = '0'
            }}
          />

        </div>

        <div className="food-poster-slideshow__caption" aria-live="polite">
          <p className="food-poster-slideshow__caption-title">{activeSlide.caption}</p>
          <p className="food-poster-slideshow__caption-detail">{activeSlide.detail}</p>
        </div>

        <div className="food-poster-slideshow__indicators" aria-label="Chọn poster">
          {slides.map((slide, index) => (
            <button
              className="food-poster-slideshow__indicator"
              key={slide.caption}
              type="button"
              aria-label={`Xem poster ${index + 1}`}
              aria-current={index === activeIndex ? 'true' : undefined}
              onClick={() => goTo(index)}
            />
          ))}
        </div>
      </div>
    </section>
  )
}

export default FoodPosterSlideshow
