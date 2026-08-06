type SkeletonProps = {
  className?: string
}

export function Skeleton({ className = '' }: SkeletonProps) {
  return <span className={`skeleton${className ? ` ${className}` : ''}`} aria-hidden="true" />
}