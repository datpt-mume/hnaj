'use client';

import { useRecommendationStore } from '@/store/useRecommendationStore';
import clsx from 'clsx';

export default function ResultModeToggle() {
  const { resultMode, setResultMode } = useRecommendationStore();

  return (
    <div className="space-y-2">
      <label className="block text-sm font-semibold text-gray-700">
        🎯 Chế độ hiển thị
      </label>

      <div className="flex gap-2">
        <button
          type="button"
          onClick={() => setResultMode('single')}
          className={clsx(
            'flex-1 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200',
            resultMode === 'single'
              ? 'bg-primary-500 text-white shadow-lg shadow-primary-200'
              : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
          )}
        >
          🎲 Chỉ 1 địa điểm
        </button>
        <button
          type="button"
          onClick={() => setResultMode('top3')}
          className={clsx(
            'flex-1 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200',
            resultMode === 'top3'
              ? 'bg-primary-500 text-white shadow-lg shadow-primary-200'
              : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
          )}
        >
          🃏 Top 3 địa điểm
        </button>
      </div>
    </div>
  );
}
