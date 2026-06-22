'use client';

import { useRecommendationStore } from '@/store/useRecommendationStore';
import PlaceCard from './PlaceCard';
import { Sparkles, AlertTriangle, RefreshCw, Frown } from 'lucide-react';

export default function RecommendationResult() {
  const {
    isLoading,
    results,
    meta,
    error,
    showEmptyState,
  } = useRecommendationStore();

  // --- Loading State ---
  if (isLoading) {
    return (
      <div className="flex flex-col items-center justify-center py-16 animate-fade-up">
        {/* Spinning wheel effect */}
        <div className="relative w-24 h-24 mb-6">
          <div className="absolute inset-0 rounded-full border-4 border-primary-100 animate-spin-slow" />
          <div className="absolute inset-2 rounded-full border-4 border-t-primary-500 border-r-transparent border-b-transparent border-l-transparent animate-spin" />
          <div className="absolute inset-0 flex items-center justify-center">
            <Sparkles className="w-8 h-8 text-primary-500 animate-pulse" />
          </div>
        </div>
        <p className="text-gray-600 font-medium text-lg">Đang khám phá...</p>
        <p className="text-gray-400 text-sm mt-1">Hệ thống đang tìm địa điểm phù hợp nhất cho bạn</p>
      </div>
    );
  }

  // --- Error State ---
  if (error) {
    return (
      <div className="flex flex-col items-center justify-center py-12 animate-fade-up">
        <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
          <AlertTriangle className="w-8 h-8 text-red-500" />
        </div>
        <p className="text-red-600 font-medium">{error}</p>
        <button
          onClick={() => window.location.reload()}
          className="mt-4 flex items-center gap-2 px-4 py-2 text-sm text-red-600 border border-red-200
                     rounded-lg hover:bg-red-50 transition-colors"
        >
          <RefreshCw className="w-4 h-4" />
          Thử lại
        </button>
      </div>
    );
  }

  // --- Empty State ---
  if (showEmptyState) {
    return (
      <div className="flex flex-col items-center justify-center py-12 animate-fade-up">
        <div className="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
          <Frown className="w-10 h-10 text-gray-400" />
        </div>
        <p className="text-gray-700 font-semibold text-lg">Không tìm thấy địa điểm phù hợp</p>
        {meta && (
          <p className="text-gray-500 text-sm mt-2 text-center max-w-sm">{meta.message}</p>
        )}
        <p className="text-gray-400 text-xs mt-4">
          💡 Mẹo: Thử giảm số lượng tags hoặc tăng bán kính tìm kiếm.
        </p>
      </div>
    );
  }

  // --- No results yet (initial state) ---
  if (results.length === 0 && !meta) {
    return null;
  }

  // --- Results ---
  return (
    <div className="space-y-4 animate-fade-up">
      {/* Fallback notice */}
      {meta?.fallback_applied && (
        <div className="flex items-start gap-2 px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl">
          <AlertTriangle className="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
          <p className="text-sm text-amber-700">{meta.message}</p>
        </div>
      )}

      {/* Place cards */}
      <div className="grid gap-4 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
        {results.map((place, idx) => (
          <PlaceCard key={place.id} place={place} index={idx} />
        ))}
      </div>

      {/* Meta info */}
      {meta && !meta.fallback_applied && (
        <p className="text-center text-xs text-gray-400 mt-4">
          ✨ {meta.message}
        </p>
      )}
    </div>
  );
}
