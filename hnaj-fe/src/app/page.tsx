'use client';

import { useRecommendationStore } from '@/store/useRecommendationStore';
import LocationPicker from '@/components/LocationPicker';
import RadiusSelector from '@/components/RadiusSelector';
import BudgetSlider from '@/components/BudgetSlider';
import TagSelector from '@/components/TagSelector';
import ResultModeToggle from '@/components/ResultModeToggle';
import RecommendationResult from '@/components/RecommendationResult';
import { Sparkles, Compass } from 'lucide-react';

export default function HomePage() {
  const { location, fetchRecommendations, isLoading } = useRecommendationStore();

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    fetchRecommendations();
  };

  return (
    <main className="max-w-2xl mx-auto px-4 py-6 pb-20">
      {/* ===== Header ===== */}
      <header className="text-center mb-8 animate-fade-up">
        <div className="inline-flex items-center gap-2 mb-2">
          <Compass className="w-8 h-8 text-primary-500" />
          <h1 className="text-3xl font-display font-extrabold text-gray-800 tracking-tight">
            HNaj
          </h1>
        </div>
        <p className="text-gray-500 text-sm text-balance">
          Không biết đi đâu? Để HNaj <strong>quyết định giúp bạn</strong> ✨
        </p>
      </header>

      {/* ===== Search Form ===== */}
      <form
        onSubmit={handleSubmit}
        className="space-y-6 bg-white/80 backdrop-blur-sm rounded-2xl p-6
                   shadow-xl shadow-gray-200/50 border border-gray-100
                   animate-fade-up"
      >
        {/* Location */}
        <LocationPicker />

        {/* Divider */}
        <hr className="border-gray-100" />

        {/* Radius */}
        <RadiusSelector />

        {/* Divider */}
        <hr className="border-gray-100" />

        {/* Budget */}
        <BudgetSlider />

        {/* Divider */}
        <hr className="border-gray-100" />

        {/* Tags */}
        <TagSelector />

        {/* Divider */}
        <hr className="border-gray-100" />

        {/* Result Mode */}
        <ResultModeToggle />

        {/* Submit CTA */}
        <button
          type="submit"
          disabled={!location || isLoading}
          className="w-full flex items-center justify-center gap-2 px-6 py-4
                     bg-gradient-to-r from-primary-500 to-primary-600
                     text-white text-lg font-bold rounded-2xl
                     hover:from-primary-600 hover:to-primary-700
                     active:scale-[0.98] transition-all duration-200
                     disabled:opacity-50 disabled:cursor-not-allowed
                     shadow-xl shadow-primary-200"
        >
          <Sparkles className="w-6 h-6" />
          {isLoading ? 'Đang khám phá...' : 'Khám phá ngay'}
        </button>
      </form>

      {/* ===== Results ===== */}
      <section className="mt-8">
        <RecommendationResult />
      </section>

      {/* ===== Footer ===== */}
      <footer className="text-center mt-12 pb-4">
        <p className="text-xs text-gray-400">
          © 2026 HNaj · Gợi ý địa điểm theo ngữ cảnh
        </p>
      </footer>
    </main>
  );
}
