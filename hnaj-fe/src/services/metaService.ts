/**
 * Filter metadata (categories, districts, tags) for the discovery screen.
 *
 * TODO(meta-api): Hiện chưa có API meta trên backend. Dữ liệu dưới đây trùng khớp với
 * database hiện tại (đã đối chiếu 2026-08-06). LƯU Ý: ID là auto-increment của từng môi
 * trường và CÓ THỂ KHÁC NHAU giữa dev/staging/prod. Khi contract `GET /api/meta/*` được
 * phê duyệt, phải thay các hằng số này bằng lời gọi API và xóa dòng TODO — đừng dùng
 * danh sách tĩnh làm nguồn ID lâu dài.
 */

export type FilterCategory = {
  id: number
  name: string
  slug: string
}

export type FilterDistrict = {
  id: number
  name: string
}

export type FilterTag = {
  id: number
  name: string
  slug: string
}

/** ID khớp database hiện tại (categories auto-increment 59-66). */
export const CATEGORIES: FilterCategory[] = [
  { id: 59, name: 'Ăn uống', slug: 'an-uong' },
  { id: 60, name: 'Cà phê & đồ uống', slug: 'ca-phe-do-uong' },
  { id: 61, name: 'Vui chơi & giải trí', slug: 'vui-choi-giai-tri' },
  { id: 62, name: 'Văn hóa & tham quan', slug: 'van-hoa-tham-quan' },
  { id: 63, name: 'Mua sắm', slug: 'mua-sam' },
  { id: 64, name: 'Thể thao & vận động', slug: 'the-thao-van-dong' },
  { id: 65, name: 'Thư giãn & làm đẹp', slug: 'thu-gian-lam-dep' },
  { id: 66, name: 'Thiên nhiên & ngoài trời', slug: 'thien-nhien-ngoai-troi' },
]

/** ID khớp database hiện tại (districts auto-increment 183-212). */
export const DISTRICTS: FilterDistrict[] = [
  { id: 183, name: 'Ba Đình' },
  { id: 184, name: 'Bắc Từ Liêm' },
  { id: 185, name: 'Cầu Giấy' },
  { id: 186, name: 'Đống Đa' },
  { id: 187, name: 'Hà Đông' },
  { id: 188, name: 'Hai Bà Trưng' },
  { id: 189, name: 'Hoàn Kiếm' },
  { id: 190, name: 'Hoàng Mai' },
  { id: 191, name: 'Long Biên' },
  { id: 192, name: 'Nam Từ Liêm' },
  { id: 193, name: 'Tây Hồ' },
  { id: 194, name: 'Thanh Xuân' },
  { id: 195, name: 'Ba Vì' },
  { id: 196, name: 'Chương Mỹ' },
  { id: 197, name: 'Đan Phượng' },
  { id: 198, name: 'Đông Anh' },
  { id: 199, name: 'Gia Lâm' },
  { id: 200, name: 'Hoài Đức' },
  { id: 201, name: 'Mê Linh' },
  { id: 202, name: 'Mỹ Đức' },
  { id: 203, name: 'Phú Xuyên' },
  { id: 204, name: 'Phúc Thọ' },
  { id: 205, name: 'Quốc Oai' },
  { id: 206, name: 'Sóc Sơn' },
  { id: 207, name: 'Thạch Thất' },
  { id: 208, name: 'Thanh Oai' },
  { id: 209, name: 'Thanh Trì' },
  { id: 210, name: 'Thường Tín' },
  { id: 211, name: 'Ứng Hòa' },
  { id: 212, name: 'Sơn Tây' },
]

/**
 * Chỉ tag active (PRD 6.2: tag inactive không xuất hiện trong lựa chọn mới).
 * ID khớp database hiện tại (tags active 171,174,175,177-179,181-185,189-194).
 */
export const TAGS: FilterTag[] = [
  { id: 171, name: 'Chill', slug: 'chill' },
  { id: 174, name: 'Yên tĩnh', slug: 'yen-tinh' },
  { id: 175, name: 'Sang trọng', slug: 'sang-trong' },
  { id: 177, name: 'Hẹn hò', slug: 'hen-ho' },
  { id: 178, name: 'Đi nhóm', slug: 'di-nhom' },
  { id: 179, name: 'Gia đình', slug: 'gia-dinh' },
  { id: 181, name: 'Học sinh — sinh viên', slug: 'hoc-sinh-sinh-vien' },
  { id: 182, name: 'Trẻ em', slug: 'tre-em' },
  { id: 183, name: 'Chấp nhận pet', slug: 'chap-nhan-pet' },
  { id: 184, name: 'Có chỗ đỗ xe', slug: 'co-cho-do-xe' },
  { id: 185, name: 'Ngoài trời', slug: 'ngoai-troi' },
  { id: 189, name: 'Mở khuya', slug: 'mo-khuya' },
  { id: 190, name: 'Đồ ăn đường phố', slug: 'do-an-duong-pho' },
  { id: 191, name: 'Ăn nhanh', slug: 'an-nhanh' },
  { id: 192, name: 'Đồ chay', slug: 'do-chay' },
  { id: 193, name: 'Đồ ngọt', slug: 'do-ngot' },
  { id: 194, name: 'Bia & nhậu', slug: 'bia-nhau' },
]

export const DEFAULT_RADIUS_KM = 5