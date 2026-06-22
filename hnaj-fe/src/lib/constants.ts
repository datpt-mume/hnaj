import { UITag } from '@/types';

/**
 * Static list of context tags used in the search form.
 * In production, these could be fetched from GET /api/v1/tags.
 */
export const DEFAULT_TAGS: UITag[] = [
  { slug: 'hen-ho',        name: 'Hẹn hò',       emoji: '💑' },
  { slug: 'gia-sinh-vien', name: 'Giá sinh viên', emoji: '🎓' },
  { slug: 'rieng-tu',      name: 'Riêng tư',      emoji: '🤫' },
  { slug: 'trong-nha',     name: 'Trong nhà',     emoji: '🏠' },
  { slug: 'ngoai-troi',    name: 'Ngoài trời',    emoji: '🌳' },
  { slug: 'the-thao',      name: 'Thể thao',      emoji: '⚽' },
  { slug: 'van-dong',      name: 'Vận động',      emoji: '🏃' },
  { slug: 'thu-gian',      name: 'Thư giãn',      emoji: '🧘' },
  { slug: 'sang-trong',    name: 'Sang trọng',    emoji: '✨' },
  { slug: 'thu-cung',      name: 'Thú cưng',      emoji: '🐶' },
  { slug: 'gia-dinh',      name: 'Gia đình',      emoji: '👨‍👩‍👧‍👦' },
  { slug: 'lam-viec',      name: 'Làm việc',      emoji: '💻' },
];

/** Format VND currency */
export function formatVND(amount: number): string {
  if (amount >= 1_000_000) {
    return `${(amount / 1_000_000).toFixed(1)}M`;
  }
  return `${(amount / 1000).toFixed(0)}k`;
}

/** Format distance */
export function formatDistance(km: number): string {
  if (km < 1) {
    return `${Math.round(km * 1000)}m`;
  }
  return `${km.toFixed(1)}km`;
}
