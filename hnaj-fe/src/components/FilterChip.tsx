type FilterChipProps = {
  id: string
  label: string
  selected: boolean
  onToggle: () => void
}

export function FilterChip({ id, label, selected, onToggle }: FilterChipProps) {
  return (
    <button
      id={id}
      className={`chip${selected ? ' chip--selected' : ''}`}
      type="button"
      aria-pressed={selected}
      onClick={onToggle}
    >
      {label}
    </button>
  )
}