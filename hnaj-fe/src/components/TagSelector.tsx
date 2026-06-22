'use client';

import { useRecommendationStore } from '@/store/useRecommendationStore';
import { DEFAULT_TAGS } from '@/lib/constants';
import clsx from 'clsx';

export default function TagSelector() {
  const { selectedTags, toggleTag } = useRecommendationStore();

  return (
    <div className="space-y-2">
      <label className="block text-sm font-semibold text-gray-700">
        🏷️ Ngữ cảnh (chọn nhiều)
      </label>

      <div className="flex flex-wrap gap-2">
        {DEFAULT_TAGS.map((tag) => {
          const isSelected = selectedTags.includes(tag.slug);
          return (
            <button
              key={tag.slug}
              type="button"
              onClick={() => toggleTag(tag.slug)}
              className={clsx(
                'inline-flex items-center gap-1.5 px-3 py-2 rounded-full text-sm font-medium',
                'transition-all duration-200 border-2',
                isSelected
                  ? 'bg-primary-500 text-white border-primary-500 shadow-lg shadow-primary-200'
                  : 'bg-white text-gray-600 border-gray-200 hover:border-primary-300 hover:bg-primary-50'
              )}
            >
              <span>{tag.emoji}</span>
              <span>{tag.name}</span>
            </button>
          );
        })}
      </div>

      {selectedTags.length > 0 && (
        <p className="text-xs text-gray-400">
          Đã chọn {selectedTags.length} tag — Hệ thống sẽ tìm quán có <strong>đầy đủ</strong> các tag này.
        </p>
      )}
    </div>
  );
}
