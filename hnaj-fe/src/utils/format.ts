/** Định dạng số tiền theo locale vi-VN; trả về null khi nhận null. */
export function formatVnd(value: number | null): string | null {
  if (value === null) return null
  return new Intl.NumberFormat('vi-VN').format(value)
}