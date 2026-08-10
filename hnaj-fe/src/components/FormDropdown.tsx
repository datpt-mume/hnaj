import { useEffect, useId, useRef, useState } from 'react'
import type { KeyboardEvent } from 'react'
import { RiArrowDownSLine, RiCheckLine } from 'react-icons/ri'

export type FormDropdownOption<T extends string | number | null = string | number | null> = {
  value: T
  label: string
  disabled?: boolean
}

type FormDropdownProps<T extends string | number | null = string | number | null> = {
  value: T
  options: Array<FormDropdownOption<T>>
  onChange: (value: T) => void
  label: string
  disabled?: boolean
}

export function FormDropdown<T extends string | number | null = string | number | null>({
  value,
  options,
  onChange,
  label,
  disabled = false,
}: FormDropdownProps<T>) {
  const componentId = useId()
  const listId = `form-dropdown-${componentId}`
  const containerRef = useRef<HTMLDivElement>(null)
  const activeOptionRef = useRef<HTMLButtonElement>(null)
  const selectedOptionIndex = options.findIndex((option) => option.value === value)
  const normalizedSelectedIndex = selectedOptionIndex >= 0 ? selectedOptionIndex : 0
  const [isOpen, setIsOpen] = useState(false)
  const [activeIndex, setActiveIndex] = useState(normalizedSelectedIndex)

  useEffect(() => {
    if (disabled) setIsOpen(false)
  }, [disabled])

  useEffect(() => {
    if (isOpen) activeOptionRef.current?.scrollIntoView({ block: 'nearest' })
  }, [activeIndex, isOpen])

  useEffect(() => {
    if (!isOpen) return

    function handlePointerDown(event: PointerEvent) {
      if (!containerRef.current?.contains(event.target as Node)) setIsOpen(false)
    }

    document.addEventListener('pointerdown', handlePointerDown)
    return () => document.removeEventListener('pointerdown', handlePointerDown)
  }, [isOpen])

  function openList() {
    if (disabled) return
    setActiveIndex(normalizedSelectedIndex)
    setIsOpen(true)
  }

  function selectOption(optionValue: T) {
    if (disabled) return
    onChange(optionValue)
    setIsOpen(false)
  }

  function handleKeyDown(event: KeyboardEvent<HTMLButtonElement>) {
    const lastIndex = options.length - 1

    if (event.key === 'Escape') {
      if (isOpen) {
        event.preventDefault()
        setIsOpen(false)
      }
      return
    }

    if (event.key === 'ArrowDown') {
      event.preventDefault()
      if (!isOpen) {
        openList()
        return
      }
      setActiveIndex((current) => Math.min(current + 1, lastIndex))
      return
    }

    if (event.key === 'ArrowUp') {
      event.preventDefault()
      if (!isOpen) {
        openList()
        return
      }
      setActiveIndex((current) => Math.max(current - 1, 0))
      return
    }

    if (event.key === 'Home' || event.key === 'End') {
      event.preventDefault()
      if (!isOpen) {
        setIsOpen(true)
      }
      setActiveIndex(event.key === 'Home' ? 0 : lastIndex)
      return
    }

    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault()
      if (!isOpen) {
        openList()
        return
      }
      const option = options[activeIndex]
      if (option && !option.disabled) selectOption(option.value)
    }
  }

  return (
    <div
      ref={containerRef}
      className="filter-combobox"
      onBlur={(event) => {
        if (!event.currentTarget.contains(event.relatedTarget as Node | null)) setIsOpen(false)
      }}
    >
      <button
        type="button"
        className="filter-combobox__trigger"
        disabled={disabled}
        aria-label={label}
        aria-haspopup="listbox"
        aria-expanded={isOpen}
        aria-controls={listId}
        aria-activedescendant={isOpen ? `${listId}-option-${activeIndex}` : undefined}
        onClick={() => {
          if (isOpen) setIsOpen(false)
          else openList()
        }}
        onKeyDown={handleKeyDown}
      >
        <span className="filter-combobox__value">{options[normalizedSelectedIndex]?.label ?? label}</span>
        <RiArrowDownSLine
          className={`filter-combobox__icon${isOpen ? ' filter-combobox__icon--open' : ''}`}
          aria-hidden="true"
        />
      </button>
      {isOpen ? (
        <div id={listId} className="filter-combobox__list" role="listbox" aria-label={label}>
          {options.map((option, index) => {
            const isSelected = option.value === value
            const isActive = index === activeIndex

            return (
              <button
                key={`${String(option.value)}-${index}`}
                id={`${listId}-option-${index}`}
                ref={isActive ? activeOptionRef : undefined}
                type="button"
                role="option"
                tabIndex={-1}
                aria-selected={isSelected}
                aria-disabled={option.disabled}
                data-active={isActive}
                className="filter-combobox__option"
                disabled={option.disabled}
                onMouseEnter={() => setActiveIndex(index)}
                onClick={() => selectOption(option.value)}
              >
                <span>{option.label}</span>
                {isSelected ? <RiCheckLine aria-hidden="true" /> : null}
              </button>
            )
          })}
        </div>
      ) : null}
    </div>
  )
}
